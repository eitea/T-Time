<?php require 'header.php'; enableToERP($userID); ?>
<?php
$filterClient = $filterProposal = 0;
$meta_curDate = $meta_deliveryDate = $meta_yourSign = $meta_yourOrder = $meta_ourSign = $meta_ourMessage = $meta_daysNetto = '';
$meta_skonto1 = $meta_skonto1Days = $meta_paymentMethod = $meta_shipmentType = $meta_representative = $meta_porto = $meta_porto_percentage = '';

//new proposal
if(!empty($_GET['nERP']) && array_key_exists($_GET['nERP'], $lang['PROPOSAL_TOSTRING'])){
  $id_num = getNextERP($_GET['nERP']);
} else {
  $id_num = getNextERP('ANG');
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['filterClient'])){
    $filterClient = $_POST['filterClient'];
  }
  if(!empty($_POST['filterProposal'])){
    $filterProposal = $_POST['filterProposal'];
  }
  if(isset($_POST['meta_curDate']) && test_Date($_POST['meta_curDate'].' 12:00:00')){
    $meta_curDate = test_input($_POST['meta_curDate'].' 12:00:00');
  }
  if(isset($_POST['meta_deliveryDate']) && test_Date($_POST['meta_deliveryDate'].' 12:00:00')){
    $meta_deliveryDate = test_input($_POST['meta_deliveryDate'].' 12:00:00');
  }
  if(isset($_POST['meta_yourSign'])){
    $meta_yourSign = test_input($_POST['meta_yourSign']);
  }
  if(isset($_POST['meta_yourOrder'])){
    $meta_yourOrder = test_input($_POST['meta_yourOrder']);
  }
  if(isset($_POST['meta_ourSign'])){
    $meta_ourSign = test_input($_POST['meta_ourSign']);
  }
  if(isset($_POST['meta_ourMessage'])){
    $meta_ourMessage = test_input($_POST['meta_ourMessage']);
  }
  if(isset($_POST['meta_daysNetto'])){
    $meta_daysNetto = intval($_POST['meta_daysNetto']);
  }
  if(isset($_POST['meta_skonto1'])){
    $meta_skonto1 = floatval($_POST['meta_skonto1']);
  }
  if(isset($_POST['meta_skonto1Days'])){
    $meta_skonto1Days = intval($_POST['meta_skonto1Days']);
  }
  if(isset($_POST['meta_paymentMethod'])){
    $meta_paymentMethod = test_input($_POST['meta_paymentMethod']);
  }
  if(isset($_POST['meta_shipmentType'])){
    $meta_shipmentType = test_input($_POST['meta_shipmentType']);
  }
  if(isset($_POST['meta_representative'])){
    $meta_representative = test_input($_POST['meta_representative']);
  }
  if(isset($_POST['meta_porto'])){
    $meta_porto = floatval($_POST['meta_porto']);
  }
  if(isset($_POST['meta_porto_percentage'])){
    $meta_porto_percentage = intval($_POST['meta_porto_percentage']);
  }
  if(isset($_POST['add_product']) && ($filterClient || $filterProposal)){
    if(!empty($_POST['add_product_name']) && !empty($_POST['add_product_quantity']) && !empty($_POST['add_product_price'])){
      $product_name = test_input($_POST['add_product_name']);
      $product_description = test_input($_POST['add_product_description']);
      $product_quantity = floatval($_POST['add_product_quantity']);
      $product_price = floatval($_POST['add_product_price']);
      $product_tax_id = intval($_POST['add_product_taxes']);
      $product_unit = test_input($_POST['add_product_unit']);
      $product_is_cash = 'FALSE';
      if(!empty($_POST['add_product_as_bar'])){
        $product_is_cash = 'TRUE';
      }
      if(!$filterProposal){ //new proposal: create proposal first
        $conn->query("INSERT INTO proposals (id_number, clientID, status, curDate, deliveryDate, yourSign, yourOrder, ourSign, ourMessage, daysNetto, skonto1, skonto1Days, paymentMethod, shipmentType, representative, porto, portoRate)
        VALUES ('$id_num', $filterClient, '0', '$meta_curDate', '$meta_deliveryDate', '$meta_yourSign', '$meta_yourOrder', '$meta_ourSign', '$meta_ourMessage', '$meta_daysNetto',
        '$meta_skonto1', '$meta_skonto1Days', '$meta_paymentMethod', '$meta_shipmentType', '$meta_representative', '$meta_porto', '$meta_porto_percentage')");
        $filterProposal = mysqli_insert_id($conn);
        echo $conn->error;
      }
      $conn->query("INSERT INTO products (proposalID, name, price, quantity, description, taxID, cash, unit)
      VALUES($filterProposal, '$product_name', '$product_price', '$product_quantity', '$product_description', $product_tax_id, '$product_is_cash', '$product_unit')");

      if(mysqli_error($conn)){
        echo $conn->error;
      } else {
        echo '<div class="alert alert-success fade in">';
        echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>O.K.: </strong>'.$lang['OK_CREATE'];
        echo '</div>';
      }
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Error: </strong>'.$lang['ERROR_MISSING_FIELDS'];
      echo '</div>';
    }
  } elseif(isset($_POST['add_product'])){
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Error: </strong>'.$lang['ERROR_MISSING_SELECTION'];
    echo '</div>';
  }
  if(!empty($_POST['delete_product']) && isset($_POST['delete_selection'])){
    foreach($_POST['delete_product'] as $i){
      $conn->query("DELETE FROM products WHERE id = $i");
    }
  }
  if(isset($_POST['update_product'])){
    $x = intval($_POST['update_product']);
    if(!empty($_POST['update_name_'.$x]) && !empty($_POST['update_price_'.$x]) && !empty($_POST['update_quantity_'.$x])){
      $product_name = test_input($_POST['update_name_'.$x]);
      $product_description = test_input($_POST['update_description_'.$x]);
      $product_quantity = floatval($_POST['update_quantity_'.$x]);
      $product_price = floatval($_POST['update_price_'.$x]);
      $product_tax_id = intval($_POST['update_tax_'.$x]);
      $product_unit = test_input($_POST['update_unit_'.$x]);
      $conn->query("UPDATE products SET name='$product_name', description='$product_description', quantity='$product_quantity', price='$product_price', taxID=$product_tax_id, unit='$product_unit' WHERE id = $x");
      if(mysqli_error($conn)){
        echo $conn->error;
      } else {
        echo '<div class="alert alert-success fade in">';
        echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>O.K.: </strong>'.$lang['OK_SAVE'];
        echo '</div>';
      }
    } else {
      echo '<div class="alert alert-success fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Error: </strong>'.$lang['ERROR_MISSING_FIELDS'];
      echo '</div>';
    }
  }
  if(isset($_POST['meta_save'])){
    if($_POST['meta_save']){ //existing
      $filterProposal = intval($_POST['meta_save']);
      $conn->query("UPDATE proposals SET curDate = '$meta_curDate', deliveryDate = '$meta_deliveryDate', yourSign = '$meta_yourSign', yourOrder = '$meta_yourOrder', ourSign = '$meta_ourSign',
        ourMessage = '$meta_ourMessage', daysNetto = '$meta_daysNetto', skonto1 = '$meta_skonto1', skonto1Days = '$meta_skonto1Days',
        paymentMethod = '$meta_paymentMethod', shipmentType = '$meta_shipmentType', representative = '$meta_representative', porto = '$meta_porto', portoRate = '$meta_porto_percentage'
        WHERE id = $filterProposal");
    } else { //new proposal
      $conn->query("INSERT INTO proposals (id_number, clientID, status, curDate, deliveryDate, yourSign, yourOrder, ourSign,
        ourMessage, daysNetto, skonto1, skonto1Days, paymentMethod, shipmentType, representative, porto, portoRate)
      VALUES ('$id_num', $filterClient, '0', '$meta_curDate', '$meta_deliveryDate', '$meta_yourSign', '$meta_yourOrder', '$meta_ourSign', '$meta_ourMessage', '$meta_daysNetto',
        '$meta_skonto1', '$meta_skonto1Days', '$meta_paymentMethod', '$meta_shipmentType', '$meta_representative', '$meta_porto', '$meta_porto_percentage')");
      $filterProposal = mysqli_insert_id($conn);
    }
    if(mysqli_error($conn)){
      echo $conn->error;
    } else {
      echo '<div class="alert alert-success fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>O.K.: </strong>'.$lang['OK_SAVE'];
      echo '</div>';
    }
  }
} // END IF POST
if(isset($_GET['num'])){
  $filterProposal = intval($_GET['num']);
}
if($filterProposal){
  $result = $conn->query("SELECT * FROM proposals WHERE id = $filterProposal");
  $row = $result->fetch_assoc();
  $id_num = $row['id_number'];
} elseif($filterClient) {
  $result = $conn->query("SELECT * FROM clientInfoData WHERE clientId = $filterClient");
  $row = $result->fetch_assoc();
  $row['yourSign'] = $row['ourSign'] = $row['yourOrder'] = $row['ourMessage'] = $row['porto'] = '';
  $row['curDate'] = $row['deliveryDate'] = getCurrentTimestamp();
} else {
  redirect('offer_proposal_process.php');
}
?>
<div class="page-header">
  <h3><?php echo $lang['OFFER'] .' - '. $lang['EDIT']." <small>$id_num</small>"; ?> <button type="button" class="btn btn-default" data-toggle="modal" data-target=".proposal_details"><i class="fa fa-cog"></i></h3>
</div>

<form method="POST">
  <div style="display:none;">
    <input type="hidden" value="<?php echo $filterClient; //proposal doesnt exist ?>" name="filterClient" />
    <input type="hidden" value="<?php echo $filterProposal; ?>" name="filterProposal" />
  </div>
  <br>
  <table class="table">
    <thead>
      <th><?php echo $lang['SAVE'] ?></th>
      <th>Name</th>
      <th><?php echo $lang['DESCRIPTION']; ?></th>
      <th><?php echo $lang['PRICE_STK']; ?></th>
      <th><?php echo $lang['QUANTITY']; ?></th>
      <th><?php echo $lang['TAXES']; ?></th>
      <th></th>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT products.*, taxRates.percentage, taxRates.description AS taxName FROM products, taxRates WHERE proposalID = $filterProposal AND taxID = taxRates.id");
      while($result && ($prod_row = $result->fetch_assoc())){
        echo '<tr>';
        echo '<td><input type="checkbox" name="delete_product[]" value="'.$prod_row['id'].'" /></td>';
        echo '<td>'.$prod_row['name'].'</td>';
        echo '<td style="max-width:500px;">'.$prod_row['description'].'</td>';
        echo '<td>'.$prod_row['price'].'</td>';
        echo '<td>'.$prod_row['quantity'].' '.$prod_row['unit'].'</td>';
        echo '<td>'.$prod_row['taxName'].' '.$prod_row['percentage'].'%</td>';
        echo '<td><a class="btn btn-default" data-toggle="modal" data-target=".modal_edit_product_'.$prod_row['id'].'" ><i class="fa fa-pencil"></i></a></td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>

<?php
mysqli_data_seek($result,0);
while($result && ($prod_row = $result->fetch_assoc())):
$x = $prod_row['id'];
//MODAL START
 ?>
  <div class="modal fade modal_edit_product_<?php echo $x ?>">
    <div class="modal-dialog modal-lg modal-content" role="document">
      <div class="modal-header">
        <h4 class="modal-title"><?php echo $prod_row['name']; ?></h4>
      </div>
      <div class="modal-body">
        <div class="container-fluid">
          <div class="col-md-6">
            <label>Name</label>
            <input type="text" class="form-control" name="update_name_<?php echo $x ?>" value="<?php echo $prod_row['name']; ?>"/>
          </div>
          <div class="col-md-6">
            <label><?php echo $lang['TAXES']; ?></label><br>
            <select class="js-example-basic-single" name="update_tax_<?php echo $x ?>" style="width:100%;">
              <?php
              $tax_result = $conn->query("SELECT * FROM taxRates WHERE percentage IS NOT NULL");
              while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
                $selected = '';
                if($tax_row['id'] == $prod_row['taxID']) { $selected = 'selected';}
                echo '<option '.$selected.' value="'.$tax_row['id'].'" >'.$tax_row['description'].' - '.$tax_row['percentage'].'% </option>';
              }
              ?>
            </select>
          </div>
        </div>
        <br>
        <div class="container-fluid">
          <div class="col-md-4">
            <label><?php echo $lang['QUANTITY']; ?></label>
            <input type="text" class="form-control" name="update_quantity_<?php echo $x ?>" value="<?php echo $prod_row['quantity']; ?>"/>
          </div>
          <div class="col-md-4">
            <label><?php echo $lang['PRICE_STK']; ?></label>
            <input type="text" class="form-control" name="update_price_<?php echo $x ?>" value="<?php echo $prod_row['price']; ?>"/>
          </div>
          <div class="col-md-4">
            <label><?php echo $lang['UNIT']; ?></label>
            <select class="js-example-basic-single" name="update_unit_<?php echo $x ?>">
              <?php
              $unit_result = $conn->query("SELECT * FROM units");
              while($unit_result && ($unit_row = $unit_result->fetch_assoc())){
                echo '<option value="'.$unit_row['unit'].'" >'.$unit_row['name'].'</option>';
              }
              ?>
            </select>
          </div>
        </div>
        <br>
        <div class="container-fluid">
          <div class="col-md-12">
            <label><?php echo $lang['DESCRIPTION']; ?></label>
            <input type="text" class="form-control" name="update_description_<?php echo $x ?>" value="<?php echo $prod_row['description']; ?>"/>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">O.K.</button>
        <button type="submit" class="btn btn-warning" name="update_product" value="<?php echo $x ?>"><?php echo $lang['SAVE'] ?></button>
      </div>
    </div>
  </div>
<?php endwhile; //MODAL END ?>

<br><br>
<div class="container-fluid">
  <div class="col-xs-6">
    <button type="submit" class="btn btn-danger" name="delete_selection"><?php echo $lang['DELETE']; ?></button>
  </div>
  <div class="col-xs-6 text-right">
    <a href="download_proposal.php?propID=<?php echo $filterProposal; ?>" target="_blank" class="btn btn-warning" >PDF Download</a>
  </div>
</div>
<br><hr>
<h5><?php echo $lang['ADD'] .': '; ?></h5>
<hr><br>
<div class="container-fluid">
  <div class="col-md-2"><label><?php echo $lang['EXISTING']; ?>:</label></div>
  <div class="col-md-3">
    <select class="js-example-basic-single" name="select_new_product_true" style="min-width:200px" onchange="displayArticle(this.value);">
      <option value="0"><?php echo $lang['ARTICLE']; ?> ...</option>
      <?php
      $result = $conn->query("SELECT articles.* FROM articles");
      while($result && ($prod_row = $result->fetch_assoc())){
        echo "<option value='".$prod_row['id']."'>".$prod_row['name']."</option>";
      }
      ?>
    </select>
  </div>
</div>
<br>
<div class="container-fluid">
  <div class="col-md-2"><label><?php echo $lang['NEW']; ?>:</label></div>
  <div class="col-md-4">
    <input type="text" class="form-control required-field" name="add_product_name" placeholder="Product Name" maxlength="48"/>
  </div>
  <div class="col-md-2">
    <input type="number" class="form-control required-field" name="add_product_quantity" placeholder="<?php echo $lang['QUANTITY']; ?>" />
  </div>
  <div class="col-md-2">
    <input type="number" step="any" class="form-control required-field" name="add_product_price" placeholder="<?php echo $lang['PRICE_STK']; ?>" />
  </div>
  <div class="col-md-2">
    <select class="js-example-basic-single" name="add_product_unit">
      <?php
      $unit_result = $conn->query("SELECT * FROM units");
      while($unit_result && ($unit_row = $unit_result->fetch_assoc())){
        echo '<option value="'.$unit_row['unit'].'" >'.$unit_row['name'].'</option>';
      }
      ?>
    </select>
  </div>
</div>
<br>
<div class="container-fluid">
  <div class="col-md-2"></div>
  <div class="col-md-4 ">
    <select class="js-example-basic-single btn-block" name="add_product_taxes">
      <?php
      $tax_result = $conn->query("SELECT * FROM taxRates WHERE percentage IS NOT NULL");
      while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
        echo '<option value="'.$tax_row['id'].'" >'.$tax_row['description'].' - '.$tax_row['percentage'].'% </option>';
      }
      ?>
    </select>
  </div>
  <div class="col-md-4 checkbox">
    <label><input type="checkbox" name="add_product_as_bar" value="TRUE" /><?php echo $lang['CASH_EXPENSE']; ?></label>
  </div>
</div>
<br>
<div class="container-fluid">
  <div class="col-md-2"></div>
  <div class="col-md-10">
    <input type="text" class="form-control" name="add_product_description" placeholder="Product Description" maxlength="190"/>
  </div>
</div>
<br><br>
<div class="container-fluid text-right">
  <button type="submit" class="btn btn-warning" name="add_product"><?php echo $lang['ADD']; ?></button>
</div>

<div class="modal fade proposal_details">
  <div class="modal-dialog modal-lg modal-content" role="document">
    <div class="modal-header">
      <h5>META: </h5>
    </div>
    <div class="modal-body">
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['DATE']; ?>:</div>
        <div class="col-md-6">
          <input type="date" class="form-control" name="meta_curDate" value="<?php echo substr($row['curDate'],0,10); ?>"/>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['DATE_DELIVERY']; ?>:</div>
        <div class="col-md-6">
          <input type="date" class="form-control" name="meta_deliveryDate" value="<?php echo substr($row['deliveryDate'],0,10); ?>" />
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['PROP_YOUR_SIGN']; ?>:</div>
        <div class="col-md-8">
          <input type="text" maxlength="25" class="form-control" name="meta_yourSign" value="<?php echo $row['yourSign']; ?>"/>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['PROP_YOUR_ORDER']; ?>:</div>
        <div class="col-md-8">
          <input type="text" maxlength="25" class="form-control" name="meta_yourOrder" value="<?php echo $row['yourOrder']; ?>"/>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['PROP_OUR_SIGN']; ?>:</div>
        <div class="col-md-8">
          <input type="text" maxlength="25" class="form-control" name="meta_ourSign" value="<?php echo $row['ourSign']; ?>"/>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['PROP_OUR_MESSAGE']; ?>:</div>
        <div class="col-md-8">
          <input type="text" maxlength="25" class="form-control" name="meta_ourMessage" value="<?php echo $row['ourMessage']; ?>" />
        </div>
      </div>
    </div>
    <div class="modal-header">
      <h5>Zahlungsdaten</h5>
    </div>
    <div class="modal-body">
      <div class="container-fluid">
        <div class="col-xs-2">
          Tage Netto
        </div>
        <div class="col-xs-8">
          <input type="number" class="form-control" name="meta_daysNetto" value="<?php echo $row['daysNetto']; ?>" />
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-xs-2">
          Skonto 1
        </div>
        <div class="col-xs-3">
          <input type="number" step="0.01" class="form-control" name="meta_skonto1" value="<?php echo $row['skonto1']; ?>" />
        </div>
        <div class="col-xs-2 text-center">
          % Innerhalb von
        </div>
        <div class="col-xs-3">
          <input type="number" class="form-control" name="meta_skonto1Days" value="<?php echo $row['skonto1Days']; ?>" />
        </div>
        <div class="col-xs-1">
          Tagen
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-xs-2">
          Zahlungsweise
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="meta_paymentMethod" value="<?php echo $row['paymentMethod']; ?>" />
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-xs-2">
          Versandart
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="meta_shipmentType" value="<?php echo $row['shipmentType']; ?>" />
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-xs-2">
          Vertreter
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="meta_representative" value="<?php echo $row['representative']; ?>" />
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-xs-2">
          Porto
        </div>
        <div class="col-xs-6">
          <input type="number" step="0.01" class="form-control" name="meta_porto" value="<?php echo $row['porto']; ?>" />
        </div>
        <div class="col-xs-3">
          <select class="js-example-basic-single" name="meta_porto_percentage">
            <?php
            $tax_result = $conn->query("SELECT * FROM taxRates WHERE percentage IS NOT NULL");
            while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
              $selected = '';
              if($row['portoRate'] == $tax_row['id']) $selected = 'selected';
              echo '<option '.$selected.' value="'.$tax_row['id'].'" >'.$tax_row['description'].' - '.$tax_row['percentage'].'% </option>';
            }
            ?>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-warning" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-warning" name="meta_save" value="<?php echo $filterProposal;?>"><?php echo $lang['SAVE'];?></button>
    </div>
  </div>
</div>
</form>

<script>
function displayArticle(i){
  if(i != ""){
    $.ajax({
      url:'ajaxQuery/AJAX_getArticles.php',
      data:{articleID: i},
      type: 'get',
      success : function(resp){
        var res = resp.split("; ");
        $("[name='add_product_name']").val(res[1]);
        $("[name='add_product_description']").val(res[2]);
        $("[name='add_product_price']").val(res[3]);
        $("[name='add_product_unit']").val(res[4]).trigger('change');
        $("[name='add_product_taxes']").val(res[5]).trigger('change');
      },
      error : function(resp){}
    });
  }
}
</script>

<?php require 'footer.php';?>
