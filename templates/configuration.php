<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*
Domain: kintpv-connect
Domain Path: ./languages
*/

    function conf_checked(&$pref, $name)
    {
        if ($pref->get_value($name, 'checked') > 0) {
            return 'checked';
        } else {
            return '';
        }
    }
?>
<style>
	#table-config_import td, #table-config_import th {border-top: 1px solid rgb(204, 204, 204); text-align: center;}
	#table-config_import td:first-child, #table-config_import th:first-child {text-align: left;}
	#table-config_import tbody tr:first-child td{border-top: 3px solid rgb(204, 204, 204);}
	.color_gradient1 td{background-color: #E3DBDB;}
	.color_gradient2 td{background-color: #FFE6D5;}
	.color_gradient3 td{background-color: #FFEEAA;}
	.color_gradient4 td{background-color: #E5FF80;}
	.color_gradient5 td{background-color: #CCFFAA;}
	.color_gradient6 td{background-color: #AFE9C6;}
	.color_gradient7 td{background-color: #AAEEFF;}
	.color_gradient8 td{background-color: #D7E3F4;}
	.color_gradient9 td{background-color: #D7D7F4;}
	.color_gradient10 td{background-color: #F6D5FF;}
	.color_gradient11 td{background-color: #E3DBE2;}
	.color_gradient0 td{background-color: #FFD5D5;}
	
	#col-left { box-sizing: border-box; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; padding-right: 15px;}

	.hidden {display: none !important;}
	
</style>
<div id="poststuff">
	<h1>KinTPV WooConnect V8 - <?php echo KINTPV_CONNECT_V8_VERSION ?> </h1>

	<div class="postbox">
		<h2 class="hndle alert"><span><?php _e("Maintenance mode", "kintpv-connect") ?></span></h2>
		<div class="misc-pub-section misc-pub-post-alert">
			<?php _e('If the maintenance mode is active on your site, the synchronisation with KinTPV will not be possible', 'kintpv-connect'); ?>
		</div>
	</div>

	<form name="form_kintpv" action="" method="post">
		
	<div class="wp-clearfix">
		<div id="col-left">
			<div class="postbox-container">
				<?php
                if ($message_erreur != '') {
                    ?>
				<div id="setting-error-apiuser" class="notice-warning settings-error notice"> 
					<p>
						<strong><span style="display: block; margin: 0.5em 0.5em 0 0; clear: both;"><?php echo $message_erreur; ?></span>
						</strong>
					</p>
				</div>
				<?php
                }
                ?>
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2 class="hndle ui-sortable-handle"><span><?php _e('WooCommerce API Key', 'kintpv-connect'); ?></span></h2>
						<div class="misc-pub-section misc-pub-post-status">
							<p>
								<label for="ident_key"><?php _e('Key', 'kintpv-connect'); ?> : </label>
								<input type="password" id="ident_key" name="ident_key" value="">
							</p><p>
								<label for="ident_secure"><?php _e('Secure', 'kintpv-connect'); ?> : </label>
								<input type="password" id="ident_secure" name="ident_secure" value="">
							</p>
						</div>
					</div>
				</div>

				<?php
                if ($cle_api == true) {
                    ?>
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2 class="hndle ui-sortable-handle"><span><?php _e('Automatic publication', 'kintpv-connect'); ?></span></h2>
						<div class="misc-pub-section misc-pub-post-status">
							<label for="etat_creation"><?php _e('State at creation', 'kintpv-connect'); ?> : </label>
							<select name="etat_creation" id="etat_creation">
								<option value="publish" 
								<?php if ($this->prefs_etat_creation->get_value(1, 'etat') == "publish") {?>
									selected
								<?php } ?> >
									<?php _e('Published', 'kintpv-connect'); ?>
								</option>

								<option value="pending"
								<?php if ($this->prefs_etat_creation->get_value(1, 'etat') == "pending") {?>
									selected
								<?php } ?> >
									<?php _e('Waiting for validation', 'kintpv-connect'); ?>
								
								</option>

								<option value="draft"
								<?php if ($this->prefs_etat_creation->get_value(1, 'etat') == "draft") {?>
									selected
								<?php } ?> >
									<?php _e('Draft', 'kintpv-connect'); ?>
								</option>
							</select>
						</div>
					</div>
				</div>

				<!-- Gestion produit hors stock -->
				<div class="postbox">
					<h2>
						<?php _e('No stock Products', 'kintpv-connect') ?>
					</h2>

					<div class="misc-pub-section misc-pub">
						<input type="checkbox" id="nostock_virtual" name="nostock_virtual" <?php if( true === $nostock_is_virtual ) echo 'checked' ?>>
						<label for="nostock_virtual"><?php _e('No stock products are virtual', 'kintpv-connect'); ?></label>
					</div>
				</div>
				<!-- \\Gestion produit hors stock -->
				
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2 class="hndle ui-sortable-handle"><span><?php _e('Taxes link', 'kintpv-connect'); ?></span></h2>
						<div class="misc-pub-section misc-pub-post-status">
							<table class="wp-list-table widefat fixed striped posts">
								<thead>
									<tr>
										<th width="5%">ID</th>
										<th>KinTPV</th>
										<th><?php _e('VAT Class', 'kintpv-connect'); ?></th>
										<th><?php _e('VAT Rate', 'kintpv-connect'); ?></th>
									</tr>
								</thead>

								<tbody>
									<?php
                                    $taxes = $this->prefs_taxe->get_liste();
                    $wc_taxes = $this->get_wc_taxes();
                    $wc_classes = $this->get_wc_classes();
                    if (!empty($taxes)) {
                        foreach ($taxes as $t) {
                            ?>
									<tr>
										<td ><?php echo $t->id; ?></td>
										<td><?php echo $t->taux; ?></td>
										<td>
											<select name="classe_<?php echo $t->id; ?>">
												<option value="0"><?php _e('Choose class', 'kintpv-connect'); ?></option>
												<?php
                                                foreach ($wc_classes as $wcc) {
                                                    ?>
												<option value="<?php echo $wcc->slug; ?>" <?php if ($wcc->slug == $t->wc_idc) {
                                                        echo 'selected';
                                                    } ?>><?php echo $wcc->name; ?></option>
												<?php
                                                } ?>
											</select>
										</td>

										<td>
											<select name="taxe_<?php echo $t->id; ?>">
												<option value="0"><?php _e('Choose rate', 'kintpv-connect'); ?></option>
												
												<?php
                                                foreach ($wc_taxes as $wct) {
                                                    ?>
												<option value="<?php echo $wct->id; ?>" <?php if ($wct->id == $t->wc_id) {
                                                        echo 'selected';
                                                    } ?>><?php echo $wct->rate; ?></option>
												<?php
                                                } ?>
											</select>
										</td>
									</tr>
									<?php
                        }
                    } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2 class="hndle ui-sortable-handle"><span><?php _e('Carriers link', 'kintpv-connect'); ?></span></h2>
						<div class="misc-pub-section misc-pub-post-status">
							<table class="wp-list-table widefat fixed striped posts">
								<thead>
									<tr>
										<th>ID</th>
										<th>WooCommerce</th>
										<th>KinTPV</th>
									</tr>
								</thead>
								<tbody>
									<?php
                                        $wc_transporteurs = $this->shipping_list();
                    $kintpv_transporteurs = $this->prefs_transporteur->get_liste();
                    foreach ($wc_transporteurs as $z) {
                        if (count($z['methods']) > 0) {
                            echo '<tr style="background-color : #ddf"><td colspan="3">Zone #'.$z['id_zone'].' - <strong>'.$z['nom'].'</strong></td></tr>';
                            foreach ($z['methods'] as $t) {
                                $id_link = $this->link_transporteurs->get_value($t->id, 'id_kintpv'); ?>
									<tr>
										<td><?php echo $t->id; ?></td>
										<td><?php echo $t->title; ?></td>
										<td>
											<select name="transporteur_<?php echo $t->id; ?>">
												<option value="0"><?php _e('Choose carrier', 'kintpv-connect'); ?></option>
												<?php
                                                if (!empty($kintpv_transporteurs)) {
                                                    foreach ($kintpv_transporteurs as $ktpvT) {
                                                        echo '<option value="'.$ktpvT->id.'" '.($id_link==$ktpvT->id? 'selected':'').'>'.$ktpvT->nom.'</option>';
                                                    }
                                                } ?>
											</select>
										</td>
									</tr>
									<?php
                            }
                        }
                    } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2 class="hndle ui-sortable-handle"><span><?php _e('Checkout methods', 'kintpv-connect'); ?></span></h2>
						<div class="misc-pub-section misc-pub-post-status">
							<table class="wp-list-table widefat fixed striped posts">
								<thead>
									<tr>
										<th>WooCommerce</th>
										<th>KinTPV</th>
									</tr>
								</thead>
								<tbody>
									<?php
                                        $wc_payments = $this->payment_list();
                    $kintpv_payments = $this->prefs_reglement->get_liste();

					function cmp($a, $b){
						$key = 'nom';
						if($a->$key < $b->$key){
							return -1;
						}else if($a->$key > $b->$key){
							return 1;
						}
						return 0;
					}

					if ($kintpv_payments) {
						usort($kintpv_payments, "cmp");
					}
                    foreach ($wc_payments as $p) {
                        $id_link = $this->link_reglements->get_value($p->id, 'id_kintpv'); ?>
									<tr>
										<td><?php echo $p->title; ?></td>
										<td>
											<select name="payments_<?php echo $p->id; ?>">
												<option value="0"><?php _e('Choose checkout method', 'kintpv-connect'); ?></option>
												<?php
                                                if (!empty($kintpv_payments)) {
                                                    foreach ($kintpv_payments as $r) {
                                                        echo '<option value="'.$r->id.'" '.($id_link == $r->id? 'selected':'').'>'.$r->nom.'</option>';
                                                    }
                                                } ?>
											</select>
										</td>
									</tr>
									<?php
                    } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				
				<!-- Box gestion états web validés -->
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2>
							<?php _e('Order states', 'kintpv-connect'); ?>
						</h2>

						<div class="misc-pub-section misc-pub-post-status">
							<table class="wp-list-table widefat fixed striped posts">
								<thead>
									<tr>
										<th><?php _e('WooCommerce state', 'kintpv-connect'); ?></th>
										<th><?php _e('Order is "validated" in KinTPV', 'kintpv-connect'); ?></th>
									</tr>
								</thead>

								<tbody>
									<?php if (isset($array_order_states)) { ?>
										<?php foreach ($array_order_states as $key => $order_state) { ?>
											<tr>
												<td>
													<?php echo $order_state ?>
												</td>

												<td>	
													<center>
														<input type="checkbox" name="order_state_<?php echo $key; ?>"
														
														<?php if (!$this->prefs_etat_commande->get($key)) {
															if ($array_default_order_states[$key] == true) {
																echo "checked";
															}
														} else {
															if ($this->prefs_etat_commande->get($key)->checked) {
																echo "checked";
															}
														} ?>>
													</center>
												</td>
											</tr>
										<?php } ?>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<!-- //Box gestion états web validés -->

				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2 class="hndle ui-sortable-handle"><span><?php _e('Orders returning to KinPTV', 'kintpv-connect'); ?></span></h2>

						<div class="misc-pub-section misc-pub-post-status">
							<label><?php _e('Quantity send', 'kintpv-connect'); ?></label>
							<?php
							
							?>
							<input type="number" min="1" value="<?php echo $nb_orders_by_sync ?>" name="orders_by_sync">
							<br>
							<small>
								<span class="dashicons dashicons-info-outline" style="font-size: 1.5em;"></span>
								<?php _e('Quantity of orders to return to KinTPV for each synchronisation', 'kintpv-connect'); ?>.
							</small>
						</div>
					</div>
				</div>
				
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2><span>XML</span></h2>
						<div class="misc-pub-section misc-pub-post-status">
							<?php
                            if ($xml_en_cours) {
                                ?>
								<p>
									<label for="cb_deboguage"><?php _e('In progress', 'kintpv-connect'); ?> : </label>
									
									<a href="<?php echo $xml_en_cours['path'].'/'.$xml_en_cours['file']; ?>" target="_blank">
										<?php echo $xml_en_cours['file']; ?>
									</a>
								</p>
							<?php
                            }
                            
                    if ($xml_termines) {
						rsort($xml_termines);
                        ?>
							<p>
							<label for="xml_termines"><?php _e('Done', 'kintpv-connect'); ?> : </label>
							<select id="xml_termines" name="xml_termines">
								<?php
                                foreach ($xml_termines as $f) {
                                    echo '<option value="'.$f['path'].'/'.$f['file'].'">'.$f['file'].'</option>';
                                } ?>
							</select>
							<input name="open_termines" class="button" type="button" value="Ouvrir" onclick="OuvrirXML('xml_termines');">
							</p>
							<?php
                    }
                            
                    if ($xml_abandonnes) {
						rsort($xml_abandonnes)
                        ?>
							<p>
							<label for="xml_abandonnes"><?php _e('Abandonned', 'kintpv-connect'); ?> : </label>
							<select id="xml_abandonnes" name="xml_abandonnes">
								<?php
                                foreach ($xml_abandonnes as $f) {
                                    echo '<option value="'.$f['path'].'/'.$f['file'].'">'.$f['file'].'</option>';
                                } ?>
							</select>
							<input name="open_termines" class="button" type="button" value="Ouvrir" onclick="OuvrirXML('xml_abandonnes');">
							
							</p>
							<?php
                    } ?>
							
							<script type="text/javascript">
								function OuvrirXML(idselect)
								{
									window.open(jQuery("#"+idselect).val());
								}
							</script>
						</div>
					</div>
				</div>
				
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2 class="hndle ui-sortable-handle"><span><?php _e('Debugging', 'kintpv-connect'); ?></span></h2>
						<div class="misc-pub-section misc-pub-post-status">
							<label for="cb_deboguage"><?php _e('Barcode', 'kintpv-connect'); ?> : </label>
							<input type="text" name="cb" id="cb_deboguage" value="" >
							<button type="button" id="bt-deug-cb" class="button tagadd">Ok</button>
							<?php if (isset($_GET['debugcb'])) {
                        ?>
							<label><?php _e('Return', 'kintpv-connect'); ?></label>
							<div style="width: 100%; overflow-x: scroll">
							<pre><?php print_r($this->get_product($_GET['debugcb'])); ?></pre>
							</div>
							<?php
                    } ?>
						</div>
					</div>
				</div>
				
				<?php
                }
                ?>
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2 class="hndle ui-sortable-handle"><span><?php _e('Logs', 'kintpv-connect'); ?></span></h2>
						<div class="misc-pub-section misc-pub-post-status">
							<a class="button" href="<?php echo $fichier_log; ?>" target="_blank"><?php _e('Open logs file', 'kintpv-connect'); ?></a>
						</div>

						<div class="misc-pub-section misc-pub-post-status">
							<?php _e('Logfile Size : ', 'kintpv-connect'); ?> <?php echo $taille_log; ?>
							<a type="button" href="javascript:;" onclick="deleteLogs()">
								<?php _e('Clear logs file', 'kintpv-connect'); ?>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
        if ($cle_api == true) {
            ?>
		<div id="col-right">
			<div class="postbox-container">
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2 class="hndle ui-sortable-handle"><span><?php _e('Import configuration', 'kintpv-connect'); ?></span></h2>
						<div class="misc-pub-section misc-pub-post-status">
							<table class="wp-list-table widefat fixed striped posts" id="table-config_import">
								<thead>
									<tr>
										<th><?php _e('Fields', 'kintpv-connect'); ?></th>
										<th><?php _e('Imported at creation', 'kintpv-connect'); ?></th>
										<th><?php _e('Imported at update', 'kintpv-connect'); ?></th>
									</tr>
								</thead>
								<thead class="color_gradient0">
									<tr>
										<?php
                                            $checkCrea = conf_checked($this->prefs_generales, 'autoriser_crea');
            $checkModif = conf_checked($this->prefs_generales, 'autoriser_modif'); ?>
										<td><?php _e('Allow Creation / Edition', 'kintpv-connect'); ?></td>
										<td>
											<input type="checkbox" onchange="javascript:checkAutorisation(this, 'crea')" name="autoriser_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'autoriser_crea'); ?>>
											<br />
											<a href="javascript:checkAll('crea');" id="checkAll_crea" <?php if (!$checkCrea) {
                echo 'class="hidden"';
            } ?>><?php _e('Check / Uncheck all', 'kintpv-connect'); ?></a>
										</td>
										<td>
											<input type="checkbox" onchange="javascript:checkAutorisation(this, 'modif')" name="autoriser_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'autoriser_modif'); ?>>
											<br />
											<a href="javascript:checkAll('modif');" id="checkAll_modif" <?php if (!$checkModif) {
                echo 'class="hidden"';
            } ?>><?php _e('Check / Uncheck all', 'kintpv-connect'); ?></a>
										</td>
									</tr>
								</thead>
								<tbody class="color_gradient1">
									<tr>
										<td><?php _e('Product name', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="nom_produit_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'nom_produit_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="nom_produit_modif" value="1"  <?php echo conf_checked($this->prefs_generales, 'nom_produit_modif'); ?>></td>
									</tr>
								</tbody>
								<tbody class="color_gradient2">
									<tr>
										<td><?php _e('Permalink (Simplified URL)', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="url_simpl_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'url_simpl_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="url_simpl_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'url_simpl_modif'); ?>></td>
									</tr>

									<tr>
										<td><?php _e('KinTPV MetaKeyword for product tags', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="metakeyword_tags_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'metakeyword_tags_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="metakeyword_tags_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'metakeyword_tags_modif'); ?>></td>
									</tr>
								</tbody>
								<tbody class="color_gradient3">
									<tr>
										<td><?php _e('Price', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="prix_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'prix_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="prix_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'prix_modif'); ?>></td>
									</tr>
									<tr>
										<td><?php _e('Taxes', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="taxes_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'taxes_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="taxes_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'taxes_modif'); ?>></td>
									</tr>
								</tbody>
								<tbody class="color_gradient4">
									<tr>
										<td><?php _e('Weight', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="poids_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'poids_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="poids_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'poids_modif'); ?>></td>
									</tr>
									<tr>
										<td><?php _e('Short descritpion', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="desc_courte_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'desc_courte_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="desc_courte_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'desc_courte_modif'); ?>></td>
									</tr>
									<tr>
										<td><?php _e('Description', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="description_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'description_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="description_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'description_modif'); ?>></td>
									</tr>
								</tbody>
								<tbody class="color_gradient5">
									<tr>
										<td><?php _e('Stock (product and variations)', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="stock_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'stock_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="stock_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'stock_modif'); ?>></td>
									</tr>
								</tbody>
								<tbody class="color_gradient6">
									<tr>
										<td><?php _e('Variations (to import product variations, check this option)', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="decli_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'decli_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="decli_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'decli_modif'); ?>></td>
									</tr>
									<tr>
										<td><?php _e('Variation price', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="prix_decli_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'prix_decli_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="prix_decli_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'prix_decli_modif'); ?>></td>
									</tr>
									<tr>
										<td><?php _e('Variation weight', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="poids_decli_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'poids_decli_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="poids_decli_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'poids_decli_modif'); ?>></td>
									</tr>
								</tbody>
								<tbody class="color_gradient7">
									<tr>
										<td><?php _e('Promotional prices', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="promos_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'promos_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="promos_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'promos_modif'); ?>></td>
									</tr>
								</tbody>
								<tbody class="color_gradient8">
									<tr>
										<td><?php _e('Possible to order if out of stock', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="cde_rupture_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'cde_rupture_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="cde_rupture_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'cde_rupture_modif'); ?>></td>
									</tr>
								</tbody>
								<tbody class="color_gradient9">
									<tr>
										<td><?php _e('Categories', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="categorie_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'categorie_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="categorie_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'categorie_modif'); ?>></td>
									</tr>
									<tr>
										<td><?php _e('Category name', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="nom_categorie_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'nom_categorie_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="nom_categorie_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'nom_categorie_modif'); ?>></td>
									</tr>
									<tr>
										<td><?php _e('Category descritpion', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="desc_categorie_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'desc_categorie_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="desc_categorie_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'desc_categorie_modif'); ?>></td>
									</tr>
									<tr>
										<td><?php _e('Category order position', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="pos_categorie_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'pos_categorie_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="pos_categorie_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'pos_categorie_modif'); ?>></td>
									</tr>
									<tr>
										<td><?php _e('Product position in the category', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="pos_prod_categorie_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'pos_prod_categorie_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="pos_prod_categorie_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'pos_prod_categorie_modif'); ?>></td>
									</tr>
								</tbody>

								<tbody class="color_gradient10">
									<?php for ($i=1; $i<13; $i++) { ?>
										<tr>
											<td>
												<?php if ($this->prefs_criteres_kintpv->get("CRITERE_1")) {
													echo _e('Criteria', 'kintpv-connect').  " {$i} \"" . $this->prefs_criteres_kintpv->get("CRITERE_{$i}")->name . "\"";
												} else {
													echo _e('Criteria', 'kintpv-connect'). $i;
												} ?>
											</td>
												<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
													echo 'hidden';
												} ?>" name="crit<?php echo $i; ?>_crea" value="<?php echo $i; ?>" <?php echo conf_checked($this->prefs_generales, 'crit'.$i.'_crea'); ?>></td>
													<td><input type="checkbox" class="modif <?php if (!$checkModif) {
													echo 'hidden';
												} ?>" name="crit<?php echo $i; ?>_modif" value="<?php echo $i; ?>" <?php echo conf_checked($this->prefs_generales, 'crit'.$i.'_modif'); ?>></td>
										</tr>
									<?php } ?>
								</tbody>
								<tbody class="color_gradient11">
									<tr>
										<td><?php _e('Product images (Attention : If you have uploaded product images directly in WooCommerce, it will be deleted at the next update from KinPTV', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="img_prod_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'img_prod_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="img_prod_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'img_prod_modif'); ?>></td>
									</tr>
									<tr>
										<td><?php _e('Category images (Attention : If you have uploaded product images directly in WooCommerce, it will be deleted at the next update from KinPTV', 'kintpv-connect'); ?></td>
										<td><input type="checkbox" class="crea <?php if (!$checkCrea) {
                echo 'hidden';
            } ?>" name="img_cat_crea" value="1" <?php echo conf_checked($this->prefs_generales, 'img_cat_crea'); ?>></td>
										<td><input type="checkbox" class="modif <?php if (!$checkModif) {
                echo 'hidden';
            } ?>" name="img_cat_modif" value="1" <?php echo conf_checked($this->prefs_generales, 'img_cat_modif'); ?>></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
        }
        ?>
	</div>
	<input name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php _e('Save changes', 'kintpv-connect'); ?>">
	</form>
</div>
<script type="text/javascript">
	
	function checkAll(type)
	{
		var cptchecked = 0;
		var cptunchecked = 0;
		var nb = jQuery("input."+type).length;
		jQuery("input."+type).each(function() {
			if(jQuery(this).is(':checked'))cptchecked++;
			else cptunchecked++;
		});
		
		if(cptchecked > cptunchecked) jQuery("input."+type).prop('checked', false);
		else jQuery("input."+type).prop('checked', true);
	}
	
	jQuery("#bt-deug-cb").click(function() {
		document.location.href="admin.php?page=kintpv-connect&debugcb="+jQuery("#cb_deboguage").val();
	});

	function checkAutorisation(chk, type)
	{
		jQuery("input."+type).each(function() {
			if (chk.checked == false) {
				jQuery(this).addClass('hidden');
				jQuery('#checkAll_'+type).addClass('hidden');
			} else {
				jQuery(this).removeClass('hidden');
				jQuery('#checkAll_'+type).removeClass('hidden');
			}
		});
	}


	function deleteLogs()
	{
		jQuery.ajax({
			url: "<?php echo $json_path; ?>/wp-json/kintpvconnect/delete_logs",
			method: "GET",
			success: function(response) {
				if (response == true) {
					document.location.reload();	
				} else {
					alert("Erreur lors du vidage du fichier de logs");
				}
			}
		});
	}
</script>