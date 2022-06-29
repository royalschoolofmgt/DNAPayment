<?php
/**
	* Feed List Page
	* Author 247Commerce
	* Date 30 SEP 2020
*/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if(!isset($_SESSION)){
	session_start();
}
if(!isset($_SESSION['247authsess'])){
	header("Location:index.php");
}
require_once('db-config.php');
require_once('config.php');
require_once('header.php');
require_once('d-header.php');
?>

<section class="connect-with" style="margin-bottom: 0">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-12">
                <div style="height: 135px;">
					<div class="float-left"><h3>Clinet Details</h3></div>
				</div>
				<div class="facts-box feeds-box" style="padding: 0; border-radius: 10px;">
					<table class="table product-feed">
                        <thead>
                          <tr class="no-border">
                            <th scope="col"><span class="grey font-14">Name</span></th>
                            <th scope="col"><span class="grey font-14">Client Id</span></th>
                            <th scope="col"><span class="grey font-14">Client Secret</span></th>
                            <th scope="col"><span class="grey font-14">Client Terminal</span></th>
                            <th scope="col"><span class="grey font-14">Actions</span></th>
                          </tr>
                        </thead>
                        <tbody>
							<?php 
								/* getting feed data from table */
								$conn = getConnection();
								$stmt = $conn->prepare("select * from dna_token_validation");
								$stmt->execute();
								$stmt->setFetchMode(PDO::FETCH_ASSOC);
								$result = $stmt->fetchAll();
								if (count($result) > 0) {
										foreach($result as $k=>$v) {
											?>
											<tr>
												<td scope="row"  data-label="Name" class="font-16"><?= $v['email_id'] ?></td>
												<td data-label="Client Id"><?= $v['client_id'] ?></td>
												<td data-label="Client Secret"><?= $v['client_secret'] ?></td>
												<td data-label="Client Terminal"><?= $v['client_terminal_id'] ?></td>
												<td data-label="Actions">
													<a href="orderDetails.php?auth=<?= base64_encode(json_encode($v['email_id'])) ?>" >Order Details</a> | 
													<a href="orderDetails.php?auth=<?= base64_encode(json_encode($v['email_id'])) ?>" >Log Details</a>
												</td>
											</tr>
								<?php	}
								}
							?>
  
                        </tbody>
                    </table>

				</div>
			</div>
		</div>
	</div>
</section>
<?php
require_once('footer.php');
?>