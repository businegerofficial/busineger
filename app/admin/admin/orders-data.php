<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-MDHS95MM');</script>
  <!-- End Google Tag Manager -->

  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Orders</title>

  <!-- DataTables / jQuery UI -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

  <!-- Same CSS as your other admin pages -->
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Arial',sans-serif;background-color:#f5f7fa;color:#333}
    a{text-decoration:none;color:inherit}
    .container{display:flex;height:100vh;overflow:hidden}

    .sidebar{width:250px;background-color:black;color:#fff;display:flex;flex-direction:column;
      padding:20px;position:fixed;left:0;top:0;bottom:0;transition:transform .3s ease-in-out;z-index:1000}
    .sidebar.active{transform:translateX(0)}
    .sidebar h2{margin-bottom:20px;text-align:center}
    .sidebar a{padding:15px;margin:10px 0;border-radius:5px;background-color:#F9D919;color:#fff;
      font-size:16px;text-align:center;transition:background-color .3s}
    .sidebar a:hover{background-color:#F9D919}
    .sidebar .close-btn{align-self:flex-end;font-size:20px;background:none;border:none;color:#fff;cursor:pointer;
      margin-bottom:20px;display:none}

    .main-content{flex:1;margin-left:250px;display:flex;flex-direction:column;overflow-y:auto;transition:margin-left .3s ease-in-out}
    .topbar{background-color:#fff;padding:20px;display:flex;justify-content:space-between;align-items:center;
      box-shadow:0 2px 4px rgba(0,0,0,.1)}
    .topbar h1{font-size:20px}

    .date-filters{margin:20px}
    .date-filters label{margin-right:10px}
    .date-filters input{padding:5px;margin-right:10px}
    #filter-date{padding:5px;margin-right:10px;background-color:#F9D919;color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:14px}
    #filter-date:hover{background-color:rgb(220,188,5)}

    .dashboard-content{padding:20px}
    .delete-btn{background:#e53935;color:#fff;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-weight:700}
    .delete-btn:hover{background:#c62828}

    @media (max-width:768px){
      .sidebar{transform:translateX(-100%)}
      .sidebar.active{transform:translateX(0)}
      .main-content{margin-left:0}
      .sidebar .close-btn{display:block}
    }
  </style>
</head>
<body>
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MDHS95MM" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

  <div class="container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-content" id="main-content">
      <?php include 'components/header.php'; ?>

      <div class="topbar"><h1>Orders</h1></div>

      <div class="date-filters">
        <label for="start-date">Start Date:</label>
        <input type="text" id="start-date" class="date-picker">
        <label for="end-date">End Date:</label>
        <input type="text" id="end-date" class="date-picker">
        <button id="filter-date">Filter</button>
      </div>

      <div class="dashboard-content">
        <table id="ordersTable" class="display">
          <thead>
            <tr>
              <th>SR</th>
              <th>Order ID</th>
              <th>User</th>
              <th>Total (₹)</th>
              <th>Status</th>
              <th>Items</th>
              <th>Cashfree Order</th>
              <th>CF Ref</th>
              <th>Created At</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

<script>
$(function () {
  const table = $('#ordersTable').DataTable({
    dom: 'Bfrtip',
    buttons: [
      { extend:'excelHtml5', exportOptions:{ columns: ':not(:last-child)' } },
      { extend:'csvHtml5',   exportOptions:{ columns: ':not(:last-child)' } },
      { extend:'print',      exportOptions:{ columns: ':not(:last-child)' } }
    ],
    ajax: { url: './api/fetch-orders.php', dataSrc: 'data' },
    columns: [
      { data:null, render:(d,t,r,m)=> m.row + 1 },
      { data:'id' },
      { data:null, render:(row)=> `${row.username ?? ''} <br><small>${row.email ?? ''}</small>` },
      { data:'total_price' },
      { data:'payment_status' },
      { data:'items_count' },
      { data:'cashfree_order_id' },
      { data:'cf_payment_reference' },
      { data:'created_at' },
      {
        data:null, orderable:false,
        render:(row)=> `<button class="delete-btn" data-id="${row.id}">Delete</button>`
      }
    ]
  });

  $('.date-picker').datepicker({ dateFormat:'yy-mm-dd' });

  $('#filter-date').on('click', function () {
    const s = $('#start-date').val(), e = $('#end-date').val();
    if (s && e) table.ajax.url(`./api/fetch-orders.php?start_date=${s}&end_date=${e}`).load();
    else alert('Please select both start and end dates.');
  });

  $(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');
    if (!confirm('Delete this order?')) return;
    $.post('./api/delete-order.php', { id })
      .done(() => table.ajax.reload())
      .fail(() => alert('Error deleting order.'));
  });
});
</script>

<script>
  const sidebar = document.getElementById('sidebar');
  const hamburger = document.getElementById('hamburger');
  const closeBtn  = document.getElementById('close-btn');
  if (hamburger && sidebar) hamburger.addEventListener('click', () => sidebar.classList.add('active'));
  if (closeBtn && sidebar)  closeBtn.addEventListener('click',  () => sidebar.classList.remove('active'));
</script>
</body>
</html>
