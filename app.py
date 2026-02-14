import os
import re
import logging
from collections import defaultdict
from flask import Flask, request, jsonify
# from flask_cors import CORS
import requests
import markdown as md
import mysql.connector
from datetime import datetime, date

# ----------------------------
# Timezone (IST) safer daily reset
# ----------------------------
try:
    from zoneinfo import ZoneInfo
    IST = ZoneInfo("Asia/Kolkata")
except Exception:
    IST = None

def now_local() -> datetime:
    return datetime.now(IST) if IST else datetime.now()

def today_local() -> date:
    return now_local().date()

# ----------------------------
# Markdown helpers
# ----------------------------
def normalize_markdown(text: str) -> str:
    t = (text or "").strip()

    # Ensure headings are on their own line
    t = re.sub(r"[ \t]*\n?(#{2,3}\s+)", r"\n\n\1", t)

    # turn "show you: - a - b - c" into real lines
    t = re.sub(r":\s*-\s+", ":\n\n- ", t)
    t = re.sub(r"(?<!\n)\s-\s(?=\S)", "\n- ", t)

    t = re.sub(
        r"(?m)^\s*-\s+(.+?)(\s+-\s+.+)+\s*$",
        lambda m: "- " + re.sub(r"\s+-\s+", "\n- ", m.group(0).strip()[2:]),
        t
    )

    # Ensure blank line before lists
    t = re.sub(r"(?m)([^\n])\n(\s*[-*]\s+)", r"\1\n\n\2", t)
    t = re.sub(r"(?m)([^\n])\n(\s*\d+\.\s+)", r"\1\n\n\2", t)

    return t.strip()

def escape_hashtag_headings(text: str) -> str:
    """
    Escapes '#tag' so Markdown doesn't treat it like a heading,
    while preserving real headings like '## Title' or '### Title'.
    """
    out = []
    for line in (text or "").splitlines():
        s = line.lstrip()

        # keep real headings like "## Title" or "### Title"
        if re.match(r"^#{2,6}\s+", s):
            out.append(line)
            continue

        # if a line starts with hashtag tag, escape the first #
        # covers "#tag" and "- #tag"
        if re.match(r"^(?:[-*+]\s+)?#\S+", s):
            i = line.find("#")
            if i != -1:
                line = line[:i] + r"\#" + line[i+1:]
        out.append(line)

    return "\n".join(out)

def markdown_to_html(text: str) -> str:
    return md.markdown(
        text,
        extensions=["extra", "sane_lists", "nl2br", "fenced_code"],
        output_format="html5",
    )

# ----------------------------
# App & CORS
# ----------------------------
app = Flask(__name__)

# If you decide to enable flask_cors later, keep it here.
# CORS(
#     app,
#     resources={
#         r"/chat": {"origins": ["https://ai-mandi.com", "https://www.ai-mandi.com", "https://bot.ai-mandi.com"]},
#         r"/usage": {"origins": ["https://ai-mandi.com", "https://www.ai-mandi.com", "https://bot.ai-mandi.com"]},
#         r"/react": {"origins": ["https://ai-mandi.com", "https://www.ai-mandi.com", "https://bot.ai-mandi.com"]},
#     },
# )

# ----------------------------
# Logging
# ----------------------------
logging.basicConfig(level=logging.INFO)
log = logging.getLogger("aimandi")

# ----------------------------
# Secrets (env)
# ----------------------------
OPENROUTER_API_KEY = os.getenv("OPENROUTER_API_KEY")
if not OPENROUTER_API_KEY:
    log.error("❌ Missing OPENROUTER_API_KEY in environment/.env")

# ----------------------------
# DB connection (MySQL)
# ❗️DO NOT TOUCH – AS REQUESTED
# ----------------------------
db = mysql.connector.connect(
    host="srv875.hstgr.io",
    user="u957188971_aipm",
    password="AIpm@1234",
    database="u957188971_aipromptmandi",
)

def _db():
    db.ping(reconnect=True, attempts=1, delay=0)
    return db

try:
    cur = _db().cursor(buffered=True)
    cur.execute("SELECT 1")
    _ = cur.fetchone()
    cur.close()
    log.info("✅ Database connection OK")
except mysql.connector.Error as err:
    log.error(f"❌ Database connection error: {err}")

# ----------------------------
# Token policy
# ----------------------------
DAILY_TOKEN_LIMIT = 8000
MAX_INPUT_CHARS   = 4000

# Marker used ONLY for stop control
FINISH_MARKER     = "<<<END>>>"

# ✅ Allow unlimited usage for these user IDs
TOKEN_WHITELIST = [10, 28, 33, 35, 45]

# ----------------------------
# Lightweight conversation memory
# ----------------------------
USER_CONTEXT = defaultdict(str)

# ----------------------------
# Helpers – Paid/unlimited detection
# ✅ Unlimited = whitelist OR purchased ANY category (active)
# Table ref from your PHP: user_access(has_paid_prompts, expires_at)
# ----------------------------
def user_has_any_active_paid_access(user_id: int) -> bool:
    cur = _db().cursor(buffered=True)
    try:
        cur.execute(
            """
            SELECT 1
            FROM user_access
            WHERE user_id = %s
              AND has_paid_prompts = 1
              AND (expires_at IS NULL OR expires_at >= NOW())
            LIMIT 1
            """,
            (user_id,),
        )
        return cur.fetchone() is not None
    except mysql.connector.Error as err:
        log.error(f"Paid check DB error: {err}")
        return False
    finally:
        cur.close()

def is_unlimited_user(user_id: int) -> bool:
    if user_id in TOKEN_WHITELIST:
        return True
    return user_has_any_active_paid_access(user_id)

# ----------------------------
# Helpers – DB usage
# (SUM is safer if duplicates exist)
# ----------------------------
def get_tokens_used_today(user_id: int) -> int:
    today = today_local()
    cur = _db().cursor(dictionary=True, buffered=True)
    try:
        cur.execute(
            """
            SELECT COALESCE(SUM(tokens_used), 0) AS tokens_used
            FROM user_token_usage
            WHERE user_id=%s AND date=%s
            """,
            (user_id, today),
        )
        row = cur.fetchone()
        return int(row["tokens_used"]) if row else 0
    finally:
        cur.close()

def add_tokens(user_id: int, tokens: int) -> None:
    today = today_local()
    cur = _db().cursor(buffered=True)
    try:
        cur.execute(
            """
            INSERT INTO user_token_usage (user_id, date, tokens_used)
            VALUES (%s, %s, %s)
            ON DUPLICATE KEY UPDATE tokens_used = tokens_used + VALUES(tokens_used)
            """,
            (user_id, today, int(tokens)),
        )
        _db().commit()
    finally:
        cur.close()

# ----------------------------
# Backend prompt_text fetch (hidden)
# ----------------------------
def get_backend_prompt_text(prompt_id: int) -> str:
    """
    Fetch prompts.prompt_text for prompt_id.
    Returns "" if not found or NULL.
    """
    if not prompt_id:
        return ""
    cur = _db().cursor(dictionary=True, buffered=True)
    try:
        cur.execute("SELECT prompt_text FROM prompts WHERE id=%s LIMIT 1", (int(prompt_id),))
        row = cur.fetchone() or {}
        txt = row.get("prompt_text")
        return (txt or "").strip()
    except Exception as e:
        log.error(f"get_backend_prompt_text error: {e}")
        return ""
    finally:
        cur.close()

# ----------------------------
# ✅ Reactions helpers
# Table: user_prompt_reactions(history_id, user_id, like_count, dislike_count)
# like_count/dislike_count: 0/1 (your current schema usage)
# ----------------------------
def get_reaction_counts(history_id: int):
    cur = _db().cursor(dictionary=True, buffered=True)
    try:
        cur.execute(
            """
            SELECT
              COALESCE(SUM(like_count), 0)    AS likes,
              COALESCE(SUM(dislike_count), 0) AS dislikes
            FROM user_prompt_reactions
            WHERE history_id=%s
            """,
            (history_id,),
        )
        row = cur.fetchone() or {}
        return int(row.get("likes", 0)), int(row.get("dislikes", 0))
    finally:
        cur.close()

def get_user_reaction(history_id: int, user_id: int) -> int:
    cur = _db().cursor(dictionary=True, buffered=True)
    try:
        cur.execute(
            """
            SELECT like_count, dislike_count
            FROM user_prompt_reactions
            WHERE history_id=%s AND user_id=%s
            LIMIT 1
            """,
            (history_id, user_id),
        )
        row = cur.fetchone()
        if not row:
            return 0
        if int(row.get("like_count", 0)) == 1:
            return 1
        if int(row.get("dislike_count", 0)) == 1:
            return -1
        return 0
    finally:
        cur.close()

def set_user_reaction(history_id: int, user_id: int, reaction: int) -> int:
    """
    reaction: 1 = like, -1 = dislike, 0 = remove
    DB stores: like_count/dislike_count as 0/1
    """
    cur = _db().cursor(buffered=True)
    try:
        if reaction == 0:
            cur.execute(
                "DELETE FROM user_prompt_reactions WHERE history_id=%s AND user_id=%s",
                (history_id, user_id),
            )
        else:
            like_val = 1 if reaction == 1 else 0
            dislike_val = 1 if reaction == -1 else 0
            cur.execute(
                """
                INSERT INTO user_prompt_reactions
                  (history_id, user_id, like_count, dislike_count, created_at, updated_at)
                VALUES
                  (%s, %s, %s, %s, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                  like_count=VALUES(like_count),
                  dislike_count=VALUES(dislike_count),
                  updated_at=NOW()
                """,
                (history_id, user_id, like_val, dislike_val),
            )

        _db().commit()
        return reaction
    finally:
        cur.close()

# ----------------------------
# History save (returns inserted history_id)
# ----------------------------
def save_prompt_history(payload: dict) -> int:
    q = """
    INSERT INTO user_prompt_history
    (user_id, category_id, topic_id, prompt_id, user_input, ai_output,
     tokens_total, tokens_input, tokens_output, status)
    VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
    """
    vals = (
        payload.get("user_id"),
        payload.get("category_id"),
        payload.get("topic_id"),
        payload.get("prompt_id"),
        payload.get("user_input"),
        payload.get("ai_output"),
        payload.get("tokens_total"),
        payload.get("tokens_input"),
        payload.get("tokens_output"),
        payload.get("status", "success"),
    )

    cur = _db().cursor(buffered=True)
    try:
        cur.execute(q, vals)
        _db().commit()
        return int(cur.lastrowid or 0)
    finally:
        cur.close()

# ----------------------------
# Mode detection (for *style*, not structure)
# ----------------------------
def detect_mode(user_msg: str) -> str:
    m = (user_msg or "").lower()
    if "prompt" in m and any(k in m for k in ["prompt for", "give me prompt", "prompts for", "prompt ideas", "ad prompt"]):
        return "PROMPT"
    if any(k in m for k in ["facebook ad", "facebook ads", "google ads", "linkedin ads", "instagram ads", "ad copy", "primary text", "headline", "description"]):
        return "ADS"
    return "GENERAL"

def detect_ad_platform(user_msg: str) -> str:
    m = (user_msg or "").lower()
    if "facebook" in m: return "Facebook Ads"
    if "instagram" in m: return "Instagram Ads"
    if "linkedin"  in m: return "LinkedIn Ads"
    if "google" in m or "search" in m: return "Google Ads"
    return "Facebook Ads"

# ----------------------------
# Acknowledgement handling
# ----------------------------
ACK_SET = {"ok", "okay", "yes", "yup", "sure", "go ahead", "alright", "proceed", "do it", "continue"}

def is_acknowledgement(msg: str) -> bool:
    return (msg or "").strip().lower() in ACK_SET

def wrap_acknowledgement(user_id: int) -> str:
    hint = USER_CONTEXT.get(user_id, "").strip()
    if not hint:
        return (
            "The user said to proceed. Continue from your last topic with concrete, "
            "next-step guidance, using clear headings and bullet points."
        )
    return (
        f"The user accepted your previous question: '{hint}'. "
        "Now answer it directly with specifics, examples and a short checklist. "
        "Use clean markdown with headings and bullet points."
    )

# ----------------------------
# Helper – limit response
# ----------------------------
def limit_reached_response(used: int):
    msg_md = (
        "## ✅ Daily limit reached\n\n"
        f"- Used today: **{used} / {DAILY_TOKEN_LIMIT}**\n"
        "- It resets automatically tomorrow. 😊\n"
    )
    msg_html = markdown_to_html(normalize_markdown(msg_md))
    return jsonify({
        "reply": msg_html,
        "reply_md": msg_md,
        "error": "limit_reached",
        "used": used,
        "remaining": 0,
        "unlimited": False,
        "limit": DAILY_TOKEN_LIMIT
    }), 429

# ----------------------------
# OpenRouter helper + auto-continue
# ----------------------------
def openrouter_call(messages, max_tokens: int):
    headers = {
        "Authorization": f"Bearer {OPENROUTER_API_KEY}",
        "HTTP-Referer": "https://bot.ai-mandi.com",
        "X-Title": "Digital-Marketing-Bot",
    }

    payload = {
        "model": "openai/gpt-5.1",
        "messages": messages,
        "max_tokens": max_tokens,
        "temperature": 0.5,
        # "stop": [FINISH_MARKER],  # disabled (you handle marker yourself)
    }

    r = requests.post(
        "https://openrouter.ai/api/v1/chat/completions",
        json=payload,
        headers=headers,
        timeout=240,
    )
    r.raise_for_status()
    data_out = r.json()

    choice = (data_out.get("choices") or [{}])[0]
    msg = (choice.get("message") or {})
    content = (msg.get("content") or "").strip()
    finish_reason = choice.get("finish_reason") or ""

    usage = data_out.get("usage") or {}
    prompt_tokens = int(usage.get("prompt_tokens") or 0)
    completion_tokens = int(usage.get("completion_tokens") or 0)
    total_tokens = int(usage.get("total_tokens") or 0)

    return content, finish_reason, prompt_tokens, completion_tokens, total_tokens, data_out

# ----------------------------
# Small parsing helpers
# ----------------------------
def _to_int_or_none(v):
    if v is None:
        return None
    if isinstance(v, bool):
        return None
    try:
        s = str(v).strip()
        if s == "" or s.lower() in ("null", "none", "undefined"):
            return None
        return int(float(s))  # handles "11", "11.0"
    except Exception:
        return None

def _safe_text(v):
    return (v or "").strip()

# ----------------------------
# Routes
# ----------------------------
@app.route("/", methods=["GET"])
def home():
    return "<h2>Digital Marketing Bot API (Token-Limited)</h2>"

@app.route("/usage", methods=["GET", "POST"])
def usage():
    if request.method == "GET":
        user_id = request.args.get("user_id")
    else:
        data = request.get_json(silent=True) or {}
        user_id = data.get("user_id")

    user_id = _to_int_or_none(user_id)
    if not user_id:
        return jsonify({"error": "User not authenticated"}), 403

    used = get_tokens_used_today(user_id)
    unlimited = is_unlimited_user(user_id)

    if unlimited:
        return jsonify({
            "used": used,
            "remaining": None,
            "unlimited": True,
            "limit": DAILY_TOKEN_LIMIT
        })

    remaining = max(0, DAILY_TOKEN_LIMIT - used)
    return jsonify({
        "used": used,
        "remaining": remaining,
        "unlimited": False,
        "limit": DAILY_TOKEN_LIMIT
    })

# ✅ Like/Dislike endpoint
@app.route("/react", methods=["POST"])
def react():
    data = request.get_json(silent=True) or {}
    user_id = _to_int_or_none(data.get("user_id"))
    history_id = _to_int_or_none(data.get("history_id"))
    reaction = _to_int_or_none(data.get("reaction"))  # 1 / -1 / 0

    if not user_id:
        return jsonify({"error": "User not authenticated"}), 403
    if not history_id or reaction is None:
        return jsonify({"error": "Invalid data"}), 400
    if reaction not in (-1, 0, 1):
        return jsonify({"error": "Invalid reaction"}), 400

    existing = get_user_reaction(history_id, user_id)
    final_reaction = 0 if existing == reaction else reaction

    try:
        set_user_reaction(history_id, user_id, final_reaction)
    except mysql.connector.Error as err:
        log.error(f"Reaction DB error: {err}")
        return jsonify({"error": "DB error"}), 500

    likes, dislikes = get_reaction_counts(history_id)

    return jsonify({
        "history_id": history_id,
        "like_count": likes,
        "dislike_count": dislikes,
        "user_reaction": final_reaction
    })

@app.route("/chat", methods=["POST"])
def chat():
    if not OPENROUTER_API_KEY:
        return jsonify({"error": "Server misconfigured: missing OPENROUTER_API_KEY"}), 500

    data = request.get_json(silent=True) or {}

    # ✅ Frontend visible label/user input
    user_prompt = _safe_text(data.get("prompt"))
    user_prompt_raw = user_prompt  # ✅ save only frontend/edited text in history

    # ✅ User identity
    user_id = _to_int_or_none(data.get("user_id"))
    if not user_id:
        return jsonify({"error": "User not authenticated"}), 403

    # ✅ Optional IDs for history + backend prompt
    category_id = _to_int_or_none(data.get("category_id"))
    topic_id    = _to_int_or_none(data.get("topic_id"))
    prompt_id   = _to_int_or_none(data.get("prompt_id"))

    # ✅ Optional legacy template (older frontend)
    # Keep this for backward compatibility (does not show in UI history).
    template = _safe_text(data.get("template"))

    if not user_prompt:
        return jsonify({"error": "Prompt is required"}), 400

    if len(user_prompt) > MAX_INPUT_CHARS:
        user_prompt = user_prompt[:MAX_INPUT_CHARS]
        user_prompt_raw = user_prompt_raw[:MAX_INPUT_CHARS]

    # ✅ Usage / limits
    used_tokens = get_tokens_used_today(user_id)
    unlimited = is_unlimited_user(user_id)
    if (not unlimited) and used_tokens >= DAILY_TOKEN_LIMIT:
        return limit_reached_response(used_tokens)

    # ✅ Acknowledgement
    if is_acknowledgement(user_prompt):
        user_prompt = wrap_acknowledgement(user_id)

    # ✅ Mode detection
    mode = detect_mode(user_prompt)
    platform_hint = detect_ad_platform(user_prompt) if mode == "ADS" else ""
    hinglish_required = "hinglish" in (user_prompt or "").lower()

    # ✅ Build style hints
    if mode == "PROMPT":
        role_hint = (
            "The user is asking for prompts, frameworks or AI instructions. "
            "Act as a senior digital marketing strategist AND expert prompt engineer. "
            "If the user gives a specific template or sections, follow that EXACTLY."
        )
        max_tokens = 2500
    elif mode == "ADS":
        role_hint = (
            f"The user is asking for advertising copy. Prefer {platform_hint} style if relevant. "
            "Act as a world-class media buyer + copywriter. "
            "If they define a structure (PIO, checklists, bullets, counts, etc.), obey it strictly."
        )
        max_tokens = 2200
    else:
        role_hint = (
            "The user is asking a general digital marketing / business / strategy question. "
            "Answer like ChatGPT: structured, logical, friendly, and expert."
        )
        max_tokens = 2200

    hinglish_hint = ""
    if hinglish_required:
        hinglish_hint = (
            "\n- The user explicitly requested Hinglish. You MUST mix Hindi and English naturally "
            "in EVERY main paragraph or bullet. Avoid pure English.\n"
        )

    system_msg = (
        "You are **AI MANDI**, a senior digital marketing strategist, copywriter, and prompt engineer. "
        f"{role_hint}\n\n"
        "GLOBAL FORMATTING RULES:\n"
        "- Always respond in clean Markdown.\n"
        "- Use '## ' and '### ' headings where helpful (do NOT overuse).\n"
        "- Use bullet lists with each item on its own line.\n"
        "- Do NOT use '>>' or slash separators like ' / ' to chain items.\n"
        "- Avoid excessive **bold**: never bold full sentences/paragraphs. Bold only 1–3 key words.\n"
        "- Keep paragraphs normal weight; use headings for emphasis.\n"
        "- Add relevant emojis in headings/bullets, but keep it professional.\n"
        f"{hinglish_hint}"
        f"- You MUST end EVERY reply with {FINISH_MARKER} on its own line.\n"
    )

    # ✅ Fetch backend prompt_text (hidden strategy/framework)
    backend_prompt = ""
    if prompt_id:
        try:
            backend_prompt = get_backend_prompt_text(prompt_id)
        except Exception:
            backend_prompt = ""

    # ✅ Combine hidden context (backend_prompt + legacy template) safely
    # Priority: backend_prompt first (your product), then template if present.
    hidden_parts = []
    if backend_prompt:
        hidden_parts.append(backend_prompt)
    if template:
        hidden_parts.append(template)
    hidden_context = "\n\n".join([p for p in hidden_parts if p]).strip()

    # ✅ Final prompt sent to model (UI still shows ONLY user_prompt_raw)
    final_user_prompt = user_prompt
    if hidden_context:
        final_user_prompt = (
            f"{hidden_context}\n\n"
            "### USER REQUEST (Frontend Prompt)\n"
            f"{user_prompt}\n"
        )

    user_prompt_wrapped = (
        f"{final_user_prompt}\n\n"
        "Reply like ChatGPT: clean headings, short paragraphs, and proper Markdown lists. "
        f"When you finish, put {FINISH_MARKER} on its own line at the very end."
    )

    # ✅ Log prompt build (this is what you were monitoring in journalctl)
    log.info(
        "Prompt build | user_id=%s prompt_id=%s backend_len=%s user_len=%s final_len=%s backend_included=%s",
        user_id,
        prompt_id,
        len(backend_prompt or ""),
        len(user_prompt_raw or ""),
        len(final_user_prompt or ""),
        bool(backend_prompt)
    )

    messages = [
        {"role": "system", "content": system_msg},
        {"role": "user", "content": user_prompt_wrapped},
    ]

    combined = []
    prompt_sum = 0
    completion_sum = 0
    total_sum = 0
    max_parts = 5
    last_finish_reason = ""

    for part in range(max_parts):
        try:
            chunk, finish_reason, p_toks, c_toks, t_toks, _data_out = openrouter_call(
                messages, max_tokens=max_tokens
            )
            log.info(
                "OpenRouter part=%s | user_id=%s | prompt_tokens=%s completion_tokens=%s total_tokens=%s finish_reason=%s",
                part + 1, user_id, p_toks, c_toks, t_toks, finish_reason
            )
        except requests.RequestException:
            log.exception("OpenRouter request error")
            return jsonify({"error": "Upstream provider error. Please try again."}), 502
        except Exception as e:
            log.exception(f"OpenRouter unknown error: {e}")
            return jsonify({"error": "Unexpected provider error."}), 502

        last_finish_reason = finish_reason

        prompt_sum += int(p_toks or 0)
        completion_sum += int(c_toks or 0)
        total_sum += int(t_toks or 0)

        if chunk:
            combined.append(chunk)

        # ✅ daily limit check uses total tokens (prompt+completion)
        if (not unlimited) and (used_tokens + total_sum) >= DAILY_TOKEN_LIMIT:
            break

        has_marker = (FINISH_MARKER in (chunk or ""))

        # ✅ If model stopped because of token limit, continue
        if finish_reason == "length":
            messages.append({"role": "assistant", "content": chunk})
            messages.append({
                "role": "user",
                "content": (
                    "Continue EXACTLY from where you left off. "
                    "Do not repeat previous lines. Keep the same Markdown formatting. "
                    f"End with {FINISH_MARKER} on its own line."
                )
            })
            continue

        # ✅ If marker exists, we are done (complete answer)
        if has_marker:
            break

        # ✅ Not length + no marker = model ended early; force continue
        messages.append({"role": "assistant", "content": chunk})
        messages.append({
            "role": "user",
            "content": (
                "You stopped before finishing. Continue and COMPLETE the answer. "
                "Do not repeat previous lines. Keep the same Markdown formatting. "
                f"End with {FINISH_MARKER} on its own line."
            )
        })
        continue

    reply_text = "\n\n".join([c for c in combined if c]).strip()
    if not reply_text:
        return jsonify({"error": "Empty response from provider."}), 500

    # Save last question for acknowledgement flow
    qmatch = re.findall(r"([^\n?.!]*\?)(?:\s|$)", reply_text)
    USER_CONTEXT[user_id] = qmatch[-1].strip() if qmatch else ""

    if FINISH_MARKER in reply_text:
        reply_text = reply_text.split(FINISH_MARKER, 1)[0].rstrip()

    reply_text = escape_hashtag_headings(reply_text)

    # ✅ DB token update
    try:
        add_tokens(user_id, total_sum)
    except mysql.connector.Error as err:
        log.error(f"DB update error: {err}")

    # ✅ Save history (IMPORTANT: user_input is ONLY frontend prompt/label)
    history_id = 0
    try:
        history_id = save_prompt_history({
            "user_id": user_id,
            "category_id": category_id,
            "topic_id": topic_id,
            "prompt_id": prompt_id,
            "user_input": user_prompt_raw,
            "ai_output": reply_text,
            "tokens_total": total_sum,
            "tokens_input": prompt_sum,
            "tokens_output": completion_sum,
            "status": "success",
        })
    except mysql.connector.Error as err:
        log.error(f"History insert error: {err}")

    # ✅ Render HTML for UI
    reply_text = normalize_markdown(reply_text)
    reply_html = markdown_to_html(reply_text)

    new_used = used_tokens + total_sum
    remaining = None if unlimited else max(0, DAILY_TOKEN_LIMIT - new_used)

    # ✅ initial counts for UI
    like_count, dislike_count = (0, 0)
    user_reaction = 0
    if history_id:
        try:
            like_count, dislike_count = get_reaction_counts(history_id)
            user_reaction = get_user_reaction(history_id, user_id)
        except mysql.connector.Error as err:
            log.error(f"Reaction read error: {err}")

    return jsonify({
        "reply": reply_html,
        "reply_md": reply_text,
        "used": new_used,
        "unlimited": unlimited,
        "limit": DAILY_TOKEN_LIMIT,
        "remaining": remaining,
        "finish_reason": last_finish_reason,

        # ✅ reactions
        "history_id": history_id,
        "like_count": like_count,
        "dislike_count": dislike_count,
        "user_reaction": user_reaction
    })

if __name__ == "__main__":
    app.logger.setLevel("DEBUG")
    app.run(host="0.0.0.0", port=5050, debug=True)
