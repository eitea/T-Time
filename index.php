<?php

$routes = array(
  'setup/run' => 'setup/setup_getInput.php',       'setup/create' => 'setup/setup.php',
  'login/auth' => 'login.php',                     'login/register' => 'selfregistration.php',

  'user/home' => 'home.php',                       'user/time' => 'timeCalcTable.php',                'user/calendar' => 'calendar.php',
  'user/logout' => 'logout.php',                   'user/request' => 'makeRequest.php',               'user/travel' => 'travelingForm.php',
  'user/ready' => 'readyPlan.php',                 'user/book' => 'userProjecting.php',

  'system/users' => 'editUsers.php',               'system/saldo' => 'admin_saldoview.php',           'system/register' => 'register_basic.php',
  'system/deactivated' => 'deactivatedUsers.php',  'system/company' => 'editCompanies.php',           'system/new' => 'new_Companies.php',
  'system/teams' => 'teamConfig.php',              'system/holidays' => 'editHolidays.php',           'system/advanced' => 'advancedOptions.php',
  'system/password' => 'passwordOptions.php',      'system/email' => 'reportOptions.php',             'system/tasks' => 'taskScheduler.php',
  'system/update' => 'pullGitRepo.php',            'system/backup' => 'download_sql.php',             'system/restore' => 'upload_database.php',
  'system/designer' => 'templateSelect.php',       'system/clients' => 'editCustomers.php',           'system/clientDetail' => 'editCustomer_detail.php',
  'system/previewTem' => 'templatePreview.php',    'system/downloadTem', 'templateDownload.php',      'system/editTem' => 'templateEdit.php',
  'system/downloadSql' => 'sqlDownload.php',

  'time/view' => 'getTimestamps.php',              'time/corrections' => 'bookAdjustments.php',       'time/travels' => 'getTravellingExpenses.php',
  'time/vacations' => 'display_vacation.php',      'time/check' => 'adminTodos.php',

  'project/bookings' => 'getProjects.php',         'project/view' => 'editProjects.php',

  'report/send' => 'sendMailReport.php',           'report/productivity'=>'report_productivity.php',  'report/autotask' => 'autotask.php',

  'erp/view' => 'offer_proposals.php',             'erp/articles' => 'product_articles.php',          'erp/taxes' => 'editTaxes.php',
  'erp/units' => 'editUnits.php',                  'erp/payment' => 'editPaymentMethods.php',         'erp/shipping' => 'editShippingMethods.php',
  'erp/representatives' => 'editRepres.php',       'erp/download' => 'download_proposal.php',         'erp/edit' => 'offer_proposal_edit.php'
);

//url must end like this:  root/ ACCESS / PAGE
$params = explode('/', $_SERVER['REQUEST_URI']);
$l = count($params) -1 ;
if($l > 1){
  $route = strtok($params[$l - 1].'/'.$params[$l], '?');
  if(array_key_exists($route, $routes)){
    $this_page = $routes[$route];
    include 'src/'.$this_page;
  } elseif($params[$l -1] == 'ajaxQuery'){
    include 'src/'.$route;
  } else {
    header('HTTP/1.0 404 Not Found');
    include '404.html';
  }
} else {
  header('Location: /login/auth');
}
