<?php
/**
 * Plugin de synchronisation entre KinTPV et WooCommerce
 *
 * @package Kintpv-Connect
 * Plugin Name: KinTPV WooConnect
 * Plugin URI: http://www.kintpv.com
 * Description: KinTPV Synchronisation plugin with WooCommerce
 * Version: 8.109
 * Author: Kinhelios
 * Author URI: http://www.kinhelios.com
 * Domain: kintpv-connect
 * Domain Path: /languages
 * package : kintpv-connect

 * Woo:
 * WC requires at least: 4.0
 * WC tested up to : 6.1
 *
 * Licence: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'WooCommerce/HttpClient/HttpClientException.php';
require_once 'WooCommerce/Client.php';
require_once 'WooCommerce/HttpClient/HttpClient.php';
require_once 'WooCommerce/HttpClient/BasicAuth.php';
require_once 'WooCommerce/HttpClient/OAuth.php';
require_once 'WooCommerce/HttpClient/Options.php';
require_once 'WooCommerce/HttpClient/Request.php';
require_once 'WooCommerce/HttpClient/Response.php';

require 'classes/kintpv_prefs.php';
require 'classes/kintpv_taxe.php';
require 'classes/kintpv_transporteur.php';
require 'classes/kintpv_reglement.php';
require 'classes/kintpv_link.php';
require 'classes/kintpv_option_import.php';
require 'classes/kintpv_etat_creation.php';
require 'classes/kintpv_etat_commande.php';
require 'classes/kintpv_synchro_commande.php';
require 'classes/kintpv_critere.php';
require 'classes/kintpv_log.php';
require 'classes/kintpv_tools.php';
require 'classes/kintpv_nostock_virtual.php';

global $wpdb;

use Automattic\WooCommerce\Client;

define( 'KINTPV_CONNECT_V8_VERSION', '8.109' );

define( 'KINTPV_PREF_TAXES', 'kintpv_pref_taxes' );
define( 'KINTPV_PREF_TRANSPORTEURS', 'kintpv_pref_transporteurs' );
define( 'KINTPV_PREF_REGLEMENTS', 'kintpv_pref_reglements' );

define( 'KINTPV_PREFS_GENERALES', 'Kintpv_Prefs_generales' );

define( 'KINTPV_LINK_TRANSPORTEURS', 'Kintpv_Link_transporteurs' );
define( 'KINTPV_LINK_REGLEMENTS', 'Kintpv_Link_reglements' );

define( 'KINTPV_PREF_ETAT_CREATION', 'kintpv_pref_etat_creation' );

define( 'KINTPV_PREF_ETAT_COMMANDE', 'kintpv_pref_etat_commande' );
define( 'KINTPV_PREF_NOSTOCK_VIRTUAL', 'kintpv_pref_nostock_virtual' );
define( 'KINTPV_PREF_SYNCHRO_COMMANDE', 'kintpv_pref_synchro_commande' );
define( 'KINTPV_PREF_CRITERE', 'kintpv_pref_critere' );

define( 'KINTPV_API_KEY', 'kintpv_api_key' );
define( 'KINTPV_API_SECURE', 'kintpv_api_secure' );

define( 'KINTPV_DOSSIER_LOG', 'logs' );
define( 'KINTPV_FICHIER_LOG', 'kintpv.log' );

define( 'KINTPV_XML_PATH', 'import_xml' );
define( 'KINTPV_XML_TRASH', 'trash' );
define( 'KINTPV_XML_OK', 'succeeded' );
define( 'KINTPV_TMP_IMG', 'tmp' );
define( 'KINTPV_IMG', 'img' );
define( 'KINTPV_IMG_ART', 'articles' );
define( 'KINTPV_IMG_CAT', 'categories' );

define( 'PER_PAGE_PARAM', 10 );

define( 'KINTPV_DEFAULT_ORDER_BY_SYNC', 20 );

define( 'NB_JOUR_FLUSH', 7 );

define( 'K_ORIGINE_RETOUR', 'R ' );

// les erreurs.
define( 'KINTPV_ERROR_NO_FILE', -100 );
define( 'KINTPV_ERROR_FILE_CREATION', -101 );
define( 'KINTPV_ERROR_FILE_ERROR', -102 );
define( 'KINTPV_ERROR_IMAGE_CREATION', -103 );
define( 'KINTPV_ERROR_IMAGE_RESIZE', -104 );
define( 'KINTPV_ERROR_PRODUCT_UNAVAILABLE', -105 );
define( 'KINTPV_ERROR_IMAGE_TOO_LARGE', -106 );
define( 'KINTPV_ERROR_CONFIG', -107 );
define( 'KINTPV_ERROR_SYNCHRO_NOT_CONFIG', -108 );
define( 'KINTPV_ERROR_SECURE_KEY', -109 );
define( 'KINTPV_ERROR_VERSION_FILE', -110 );
define( 'KINTPV_ERROR_CATEGORY_UNAVAILABLE', -111 );
define( 'KINTPV_ERROR_TAX_NOT_CONFIGURED', -112 );
define( 'KINTPV_ERROR_LANGUAGE', -113 );
define( 'KINTPV_ERROR_MANUFACTURER_UNAVAILABLE', -114 );
define( 'KINTPV_ERROR_FOLDER_CREATION', -115 );
define( 'KINTPV_PRODUCT_PRICE_ERROR', -116 );
define( 'KINTPV_PRODUCT_EAN13_ERROR', -117 );
define( 'KINTPV_WC_ERROR', -118 );
define( 'KINTPV_WC_SLUG_LENGTH', -119 );

define( 'KINTPV_ERROR_GET_ORDERS', -200 );
define( 'KINTPV_ERROR_GET_RETURNS', -201 );

define( 'KINTPV_EXT_ZIP', '.zip' );
define( 'KINTPV_EXT_XML', '.xml' );

define( 'KINTPV_META_ID_ARTICLE', '_kintpv_id_article' );

$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

/**
 * Check if WooCommerce is active
 */
if (
	in_array( $plugin_path, wp_get_active_and_valid_plugins(), true )
) {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
		/**
		 * Classe KinTPV Connect
		 *
		 * Classe du module WooConnect connectant WooCommerce et kinTPV
		 */
		class KinTPVConnect {
			/**
			 * Objet de l'API
			 *
			 * @var Client $rest_client
			 */
			protected $rest_client;

			/**
			 * Préférence des liaisons des taxes
			 *
			 * @var Kintpv_Prefs $prefs_taxe
			 */
			protected $prefs_taxe;

			/**
			 * Préférence de liaison des transporteurs
			 *
			 * @var Kintpv_Prefs $prefs_transporteur
			 */
			protected $prefs_transporteur;

			/**
			 * Préférence de liaison des règlements
			 *
			 * @var Kintpv_Prefs $prefs_reglement
			 */
			protected $prefs_reglement;

			/**
			 * Préférence de l'état des produits à la création
			 *
			 * @var Kintpv_Prefs $prefs_etat_creation
			 */
			protected $prefs_etat_creation;

			/**
			 * Préférence des états de commandes pour KinTPV
			 *
			 * @var Kintpv_Prefs $prefs_etat_commande
			 */
			protected $prefs_etat_commande;

			/**
			 * Préférence des critères
			 *
			 * @var Kintpv_Prefs $prefs_criteres_kintpv
			 */
			protected $prefs_criteres_kintpv;

			/**
			 * Préférences de synchro
			 *
			 * @var Kintpv_Prefs $prefs_generales
			 */
			protected $prefs_generales;

			/**
			 * Préférence du nombre de commandes par synchro
			 *
			 * @var prefs_synchro_commande
			 */
			protected $prefs_synchro_commande;

			/**
			 * Préférence du statut de produit virtuel ou non pour les produits hors-stock
			 * 
			 * @var prefs_nostock_virtual
			 */
			protected $prefs_nostock_virtual;

			/**
			 * Tableau des critères
			 *
			 * @var Kintpv_Critere $tab_criteres
			 */
			protected $tab_criteres;

			/**
			 * Clé API
			 *
			 * @var String $rest_key
			 */
			protected $rest_key;

			/**
			 * Secret API
			 *
			 * @var String $rest_secret
			 */
			protected $rest_secret;

			/**
			 * Taxes woocommerce
			 *
			 * @var $wc_taxes
			 */
			protected $wc_taxes;

			/**
			 * Id de la dernière commande reçue par KinTPV
			 *
			 * @var Int $last_cmd_received
			 */
			protected $last_cmd_received;

			/**
			 * Id du dernier retour de commande reçu par KinTPV
			 *
			 * @var Int $last_ret_received
			 */
			protected $last_ret_received;

			/**
			 * Date de la dernière commande reçue par KinTPV
			 *
			 * @var String $last_cmd_date
			 */
			protected $last_cmd_date;

			/**
			 * Date du dernier retour de commande reçu par KinTPV
			 *
			 * @var String $last_cmd_date
			 */
			protected $last_ret_date;

			/**
			 * Numéro de l'image produit
			 *
			 * @var Int $num
			 */
			protected $num;

			/**
			 * Lien des transporteurs
			 *
			 * @var Kintpv_Prefs $link_transporteurs
			 */
			protected $link_transporteurs;

			/**
			 * Lien des règlement
			 *
			 * @var Kintpv_Prefs $link_reglements
			 */
			protected $link_reglements;

			/**
			 * Tableau des ids des préférences
			 *
			 * @var Array $tab_id_prefs
			 */
			protected $tab_id_prefs;

			/**
			 * Numero d'erreur
			 *
			 * @var Int $error
			 */
			protected $error;

			/**
			 * Tableau des urls des catégories
			 *
			 * @var Array $url_categories
			 */
			protected $urls_categories;

			/**
			 * Tableau des ids des catégories
			 *
			 * @var Array $ids_categories
			 */
			protected $ids_categories;

			/**
			 * Caractères indésirables pour le slug
			 *
			 * @var Array $unwanted_chars
			 */
			protected $unwanted_chars;

			/**
			 * Timer de départ d'une action
			 *
			 * @var Double $start_time
			 */
			protected $start_time;

			/**
			 * Timer de fin de l'action calculée
			 *
			 * @var Double $end_time
			 */
			protected $end_time;

			/**
			 * Attribus WooCommerce
			 *
			 * @var $wc_attributes
			 */
			protected $wc_attributes;

			protected $m_is_service_hs;

			/**
			 * Méthode __construct
			 *
			 * Appelée à l'instanciation de la classe
			 */
			public function __construct() {
				$this->error           = 0;
				$this->urls_categories = array();
				$this->ids_categories  = array();
				$this->wc_attributes   = null;

				if ( isset( $_GET['k'] ) && isset( $_GET['s'] ) ) {
					$this->rest_key          = sanitize_text_field( wp_unslash( $_GET['k'] ) );
					$this->rest_secret       = sanitize_text_field( wp_unslash( $_GET['s'] ) );
					$this->last_cmd_received = ( isset( $_GET['cmd'] ) && 0 !== sanitize_text_field( wp_unslash( $_GET['cmd'] ) ) ) ? ( sanitize_text_field( wp_unslash( $_GET['cmd'] ) ) ) : 0;
					$this->last_ret_received = ( isset( $_GET['ret'] ) ) ? ( sanitize_text_field( wp_unslash( $_GET['ret'] ) ) ) : 0;
					$this->last_cmd_date     = ( isset( $_GET['cmddate'] ) && sanitize_text_field( wp_unslash( $_GET['cmddate'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['cmddate'] ) ) : '0000-00-00T00:00:00';
					$this->last_ret_date     = ( isset( $_GET['retdate'] ) && sanitize_text_field( wp_unslash( $_GET['retdate'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['retdate'] ) ) : '0000-00-00T00:00:00';
					$this->num               = ( isset( $_GET['num'] ) && (int) sanitize_text_field( wp_unslash( $_GET['num'] > 0 ) ) ) ? sanitize_text_field( wp_unslash( $_GET['num'] ) ) : 0;
				}

				add_action(
					'rest_api_init',
					function () {
						register_rest_route(
							'kintpvconnect',
							'/import',
							array(
								'methods'             => 'POST',
								'callback'            => function () {
									return $this->import_xml();
								},
								'permission_callback' => '__return_true',
							)
						);
					}
				);

				add_action(
					'rest_api_init',
					function () {
						register_rest_route(
							'kintpvconnect',
							'/images',
							array(
								'methods'             => 'POST',
								'callback'            => function () {
									return $this->import_image();
								},
								'permission_callback' => '__return_true',
							)
						);
					}
				);

				add_action(
					'rest_api_init',
					function () {
						register_rest_route(
							'kintpvconnect',
							'/orders',
							array(
								'methods'             => 'POST',
								'callback'            => function () {
									return $this->export_cmd();
								},
								'permission_callback' => '__return_true',
							)
						);
					}
				);

				add_action(
					'rest_api_init',
					function () {
						register_rest_route(
							'kintpvconnect',
							'/delete_logs',
							array(
								'methods'             => 'GET',
								'callback'            => function () {
									return $this->delete_logs();
								},
								'permission_callback' => '__return_true',
							)
						);
					}
				);

				add_action(
					'admin_menu',
					array(
						$this,
						'add_admin_menu',
					),
					100
				);

				$this->rest_client = false;

				$this->tab_id_prefs = array(
					'autoriser',
					'nom_produit',
					'url_simpl',
					'metakeyword_tags',
					'prix',
					'taxes',
					'poids',
					'desc_courte',
					'description',
					'stock',
					'decli',
					'prix_decli',
					'poids_decli',
					'promos',
					'cde_rupture',
					'categorie',
					'nom_categorie',
					'desc_categorie',
					'pos_categorie',
					'pos_prod_categorie',
					'crit1',
					'crit2',
					'crit3',
					'crit4',
					'crit5',
					'crit6',
					'crit7',
					'crit8',
					'crit9',
					'crit10',
					'crit11',
					'crit12',
					'img_prod',
					'img_cat',
				);

				// chargement des taxes WooCommerce.
				$this->wc_taxes           = null;
				$this->prefs_taxe         = null;
				$this->prefs_transporteur = null;
				$this->prefs_reglement    = null;
				$this->link_transporteurs = null;
				$this->link_reglements    = null;
				$this->prefs_generales    = null;

				$this->unwanted_chars = array(
					'Š' => 'S',
					'š' => 's',
					'Ž' => 'Z',
					'ž' => 'z',
					'À' => 'A',
					'Á' => 'A',
					'Â' => 'A',
					'Ã' => 'A',
					'Ä' => 'A',
					'Å' => 'A',
					'Æ' => 'A',
					'Ç' => 'C',
					'È' => 'E',
					'É' => 'E',
					'Ê' => 'E',
					'Ë' => 'E',
					'Ì' => 'I',
					'Í' => 'I',
					'Î' => 'I',
					'Ï' => 'I',
					'Ñ' => 'N',
					'Ò' => 'O',
					'Ó' => 'O',
					'Ô' => 'O',
					'Õ' => 'O',
					'Ö' => 'O',
					'Ø' => 'O',
					'Ù' => 'U',
					'Ú' => 'U',
					'Û' => 'U',
					'Ü' => 'U',
					'Ý' => 'Y',
					'Þ' => 'B',
					'ß' => 'Ss',
					'à' => 'a',
					'á' => 'a',
					'â' => 'a',
					'ã' => 'a',
					'ä' => 'a',
					'å' => 'a',
					'æ' => 'a',
					'ç' => 'c',
					'è' => 'e',
					'é' => 'e',
					'ê' => 'e',
					'ë' => 'e',
					'ì' => 'i',
					'í' => 'i',
					'î' => 'i',
					'ï' => 'i',
					'ð' => 'o',
					'ñ' => 'n',
					'ò' => 'o',
					'ó' => 'o',
					'ô' => 'o',
					'õ' => 'o',
					'ö' => 'o',
					'ø' => 'o',
					'ù' => 'u',
					'ú' => 'u',
					'û' => 'u',
					'ý' => 'y',
					'þ' => 'b',
					'ÿ' => 'y',
				);
			}

			/**
			 * Méthode __destruct
			 *
			 * Méthode appelée à la destruction de l'objet de la classe
			 */
			public function __destruct() {
				Kintpv_Log::close();
			}

			/**
			 * Méthode add_admin_menu
			 *
			 * Ajoute un élément dans le menu pour accéder à la page de configuration du module
			 */
			public function add_admin_menu() {
				add_submenu_page( 'woocommerce', 'KinTPV WooConnect', 'KinTPV WooConnect', 'manage_options', 'kintpvconnect', array( $this, 'menu_html' ) );
			}

			/**
			 * Méthode get_web_content_path
			 *
			 * Retourne le chemin web du module
			 *
			 * @param string $systeme_path : Chemin d'accès système au module.
			 */
			protected function get_web_content_path( $systeme_path ) {
				$path_content = $this->get_chemin_content();
				$content_dir  = basename( $path_content );
				$pos          = strpos( $systeme_path, $content_dir );
				$chemin_web   = '';

				if ( $pos > 0 ) {
					$chemin_web = $path_content . substr( $systeme_path, $pos + strlen( $content_dir ) + 1 );
				}
				return $chemin_web;
			}

			/**
			 * Méthode menu_html
			 *
			 * Configure les valeurs puis affiche la page de configuration du module
			 */
			public function menu_html() {
				$this->charger_prefs();

				// enregistrement des preferences.
				if ( isset( $_POST['save'] ) ) {
					$this->save_prefs();
				}

				// test de la connexion API.
				$this->rest_key    = get_option( KINTPV_API_KEY );
				$this->rest_secret = get_option( KINTPV_API_SECURE );
				// lecture des taxes pour verifier que l'api fonctionne.
				$retour         = $this->wc_get( 'taxes', null, false );
				$message_erreur = '';
				if ( null === $retour || isset( $retour->code ) ) {
					$cle_api = false;
					if ( null !== $retour && isset( $retour->message ) ) {
						$message_erreur = $retour->message;
					} else {
						$message_erreur = 'API non disponible ' . print_r( $retour, true );
					}
				} else {
					$cle_api = true;
				}

				// gestion des fichiers XML.
				$path           = $this->get_xml_path();
				$xml_en_cours   = false;
				$xml_termines   = array();
				$xml_abandonnes = array();
				$path_content   = $this->get_chemin_content();

				// pour l'xml courant.
				if ( is_dir( $path ) ) {
					if ( $dir = opendir( $path ) ) {
						while ( $file = readdir( $dir ) && false === $xml_en_cours ) {
							if ( ( ! is_dir( $path . '/' . $file ) ) && '.' != $file && '..' != $file ) {
								$xml_en_cours = array(
									'path' => $this->get_web_content_path( $path ),
									'file' => $file,
								);
							}
						}
						closedir( $dir );
					}
				}

				// les terminés.
				if ( is_dir( $path . '/' . KINTPV_XML_OK ) ) {
					if ( $dir = opendir( $path . '/' . KINTPV_XML_OK ) ) {
						while ( $file = readdir( $dir ) ) {
							if ( ( ! is_dir( $path . '/' . $file ) ) && '.' != $file && '..' != $file ) {
								$xml_termines[] = array(
									'path' => $this->get_web_content_path( $path . '/' . KINTPV_XML_OK ),
									'file' => $file,
								);
							}
							krsort( $xml_termines );
						}
						closedir( $dir );
					}
				}

				// les abandonnés.
				if ( is_dir( $path . '/' . KINTPV_XML_TRASH ) ) {
					if ( $dir = opendir( $path . '/' . KINTPV_XML_TRASH ) ) {
						while ( $file = readdir( $dir ) ) {
							if ( ( ! is_dir( $path . '/' . $file ) ) && '.' != $file && '..' != $file ) {
								$xml_abandonnes[] = array(
									'path' => $this->get_web_content_path( $path . '/' . KINTPV_XML_TRASH ),
									'file' => $file,
								);
							}

							krsort( $xml_abandonnes );
						}
						closedir( $dir );
					}
				}

				// chemin du fichier log.
				$fichier_log = $this->get_web_content_path( Kintpv_Log::log_path() . '/' . KINTPV_FICHIER_LOG );

				$json_path = get_site_url();
				
				// Taille du fichier log.
				$taille_log = filesize( Kintpv_Log::log_path() . '/' . KINTPV_FICHIER_LOG );

				$suffixe_taille_log = '';
				if ($taille_log < 1024) {
					$taille_log = $taille_log;
					$suffixe_taille_log = ' octets';
				} else {
					if ($taille_log >= 1024) {
						$taille_log = round($taille_log / 1024, 0);
						$suffixe_taille_log = ' Ko';
					}

					if ($taille_log >= 1024) {
						$taille_log = round($taille_log / 1024, 0);
						$suffixe_taille_log = ' Mo';
					}

					if ($taille_log >= 1024) {
						$taille_log = round($taille_log / 1024, 2);
						$suffixe_taille_log = ' Go';
					}
				}

				$taille_log = $taille_log . $suffixe_taille_log;

				// Récupération des états de commande.
				$array_order_states = array(
					'pending'    => 'Attente paiement',
					'processing' => 'En cours',
					'on-hold'    => 'En attente',
					'completed'  => 'Terminée',
					'cancelled'  => 'Annulée',
					'refunded'   => 'Remboursée',
					'failed'     => 'Échoué',
				);

				$array_default_order_states = array(
					'pending'    => false,
					'processing' => true,
					'on-hold'    => true,
					'completed'  => true,
					'cancelled'  => true,
					'refunded'   => true,
					'failed'     => false,
				);

				$pref_nb_orders_by_sync = $this->prefs_synchro_commande->get( 'orders_by_sync' );
				$nb_orders_by_sync      = ( null !== $pref_nb_orders_by_sync && (int) $pref_nb_orders_by_sync->value > 0 )
				? (int) $pref_nb_orders_by_sync->value
				: KINTPV_DEFAULT_ORDER_BY_SYNC;

				$pref_nostock_virtual = $this->prefs_nostock_virtual->get( 'is_virtual' );
				$nostock_is_virtual = ( null !== $pref_nostock_virtual ) ? $pref_nostock_virtual->checked : false;

				// affichage des reglages.
				$plugin_rel_path = basename( dirname( __FILE__ ) ) . '/languages'; /* Relative to WP_PLUGIN_DIR */
				load_plugin_textdomain( 'kintpv-connect', false, $plugin_rel_path );

				include 'templates/configuration.php';
			}

			/**
			 * Méthode charger_prefs
			 *
			 * Récupère les préférences de KinTPV Connect dans la base de données
			 */
			public function charger_prefs() {
				// chargement des paramètres.
				if ( null === $this->prefs_taxe ) {
					$this->prefs_taxe = new Kintpv_Prefs( get_option( KINTPV_PREF_TAXES ) );
				}

				if ( null === $this->prefs_transporteur ) {
					$this->prefs_transporteur = new Kintpv_Prefs( get_option( KINTPV_PREF_TRANSPORTEURS ) );
				}

				if ( null === $this->prefs_reglement ) {
					$this->prefs_reglement = new Kintpv_Prefs( get_option( KINTPV_PREF_REGLEMENTS ) );
				}

				if ( null === $this->link_transporteurs ) {
					$this->link_transporteurs = new Kintpv_Prefs( get_option( KINTPV_LINK_TRANSPORTEURS ) );
				}

				if ( null === $this->link_reglements ) {
					$this->link_reglements = new Kintpv_Prefs( get_option( KINTPV_LINK_REGLEMENTS ) );
				}

				if ( null === $this->prefs_generales ) {
					$this->prefs_generales = new Kintpv_Prefs( get_option( KINTPV_PREFS_GENERALES ) );
				}

				if ( null === $this->prefs_etat_creation ) {
					$this->prefs_etat_creation = new Kintpv_Prefs( get_option( KINTPV_PREF_ETAT_CREATION ) );
				}

				if ( null === $this->prefs_etat_commande ) {
					$this->prefs_etat_commande = new Kintpv_Prefs( get_option( KINTPV_PREF_ETAT_COMMANDE ) );
				}

				if ( null === $this->prefs_synchro_commande ) {
					$this->prefs_synchro_commande = new Kintpv_Prefs( get_option( KINTPV_PREF_SYNCHRO_COMMANDE ) );
				}

				if ( null === $this->prefs_criteres_kintpv ) {
					$this->prefs_criteres_kintpv = new Kintpv_Prefs( get_option( KINTPV_PREF_CRITERE ) );
				}

				if ( null === $this->prefs_nostock_virtual) {
					$this->prefs_nostock_virtual = new Kintpv_Prefs( get_option( KINTPV_PREF_NOSTOCK_VIRTUAL ) );
				}
			}

			/**
			 * Méthode get_node_value
			 *
			 * Récupère une valeur d'une balise XML
			 *
			 * @param string $node : Contenu de la balise.
			 * @param string $name : nom de la valeur à rechercher.
			 * @param bool   $cdata ( default = false) : Recherche-t-on une constante ou pas.
			 */
			public static function get_node_value( $node, $name, $cdata = false ) {
				$value = null;
				$tag   = $node->getElementsByTagName( $name );

				if ( $tag && $tag->length > 0 ) {
					if ( $cdata ) {
						foreach ( $tag->item( 0 )->childNodes as $child ) {
							if ( XML_CDATA_SECTION_NODE === $child->nodeType ) {
								$value = $child->textContent;
							}
						}
					} else {
						$value = $tag->item( 0 )->textContent;
					}
				}
				return $value;
			}

			/**
			 * Méthode format_float
			 *
			 * Retourne une valeur 'float' au bon format pour WooCommerce, où seront remplacées les ',' par des '.'
			 *
			 * @param string $number : Nombre au format chaine de caractère.
			 */
			public static function format_float( $number ) {
				return str_replace( ',', '.', $number );
			}

			/**
			 * Méthode option_checked
			 *
			 * Vérifie si une option de synchro est cochée ou non
			 *
			 * @param string $name : Nom de l'option à vérifier.
			 * @param bool   $creation ( default = true ) : On vérifie l'option en 'création'( true ) ou en modification( false ).
			 */
			protected function option_checked( $name, $creation = true ) {
				if ( $creation ) {
					$suffixe = '_crea';
				} else {
					$suffixe = '_modif';
				}

				if ( (int) $this->prefs_generales->get_value( $name . $suffixe, 'checked' ) > 0 ) {
					return true;
				} else {
					return false;
				}
			}

			/**
			 * Methode format_denied_chars
			 *
			 * Méthode de formattage des charactères refusés par Wordpress/WooCommerce
			 *
			 * @param string $string : Chaine de caractères à formater.
			 */
			public static function format_denied_chars( $string ) {
				return str_replace( array( '<', '>', ';', '=', '#', '{', '}' ), ' ', $string );
			}

			/**
			 * Méthode save_prefs
			 *
			 * Enregistre les préférences du module
			 */
			protected function save_prefs() {
				// enregistrement des clés API.
				if ( isset( $_POST['ident_key'] ) && '' !== $_POST['ident_key'] && isset( $_POST['ident_secure'] ) && '' !== $_POST['ident_secure'] ) {
					$this->save_option( KINTPV_API_KEY, $_POST['ident_key'] );
					$this->save_option( KINTPV_API_SECURE, $_POST['ident_secure'] );
				}

				$this->rest_key    = get_option( KINTPV_API_KEY );
				$this->rest_secret = get_option( KINTPV_API_SECURE );

				if ( $this->get_rest_client( false ) ) {
					// enregistrement de l'état à la création.
					$crea = new Kintpv_Etat_Creation();

					$crea->id   = 1;
					$crea->etat = ( isset( $_POST['etat_creation'] ) ) ? $_POST['etat_creation'] : 'publish';

					$this->prefs_etat_creation->set( $crea );
					$this->save_option( KINTPV_PREF_ETAT_CREATION, $this->prefs_etat_creation->serialize() );

					// enregistrement de la liaison des taxes.
					$taxes = $this->prefs_taxe->get_liste();
					if ( $taxes ) {
						foreach ( $taxes as $t ) {
							$t->wc_id  = ( isset( $_POST[ 'taxe_' . $t->id ] ) ) ? $_POST[ 'taxe_' . $t->id ] : 0;
							$t->wc_idc = ( isset( $_POST[ 'classe_' . $t->id ] ) ) ? $_POST[ 'classe_' . $t->id ] : 0;
							$this->prefs_taxe->set( $t );
						}
						$this->save_option( KINTPV_PREF_TAXES, $this->prefs_taxe->serialize() );
					}
					// enregistrement de la liaison des transporteurs.
					$wc_transporteurs = $this->shipping_list();

					foreach ( $wc_transporteurs as $t ) {
						foreach ( $t['methods'] as $m ) {
							$link_t            = new Kintpv_Link();
							$link_t->id        = $m->id;
							$link_t->id_kintpv = isset( $_POST[ 'transporteur_' . $m->id ] ) ? $_POST[ 'transporteur_' . $m->id ] : 0;

							$this->link_transporteurs->set( $link_t );
						}
					}
					$this->save_option( KINTPV_LINK_TRANSPORTEURS, $this->link_transporteurs->serialize() );

					// enregistrement de la liaison des reglements.
					$wc_reglements = $this->payment_list();
					foreach ( $wc_reglements as $r ) {
						$link_r            = new Kintpv_Link();
						$link_r->id        = $r->id;
						$link_r->id_kintpv = isset( $_POST[ 'payments_' . $r->id ] ) ? $_POST[ 'payments_' . $r->id ] : 0;

						$this->link_reglements->set( $link_r );
					}
					$this->save_option( KINTPV_LINK_REGLEMENTS, $this->link_reglements->serialize() );
					// enregistrement des préférences générales.
					foreach ( $this->tab_id_prefs as $p ) {
						// la pref de creation.
						$id_pref       = $p . '_crea';
						$pref          = new Kintpv_Option_Import();
						$pref->id      = $id_pref;
						$pref->checked = false;

						if ( isset( $_POST[ $id_pref ] ) && $_POST[ $id_pref ] > 0 ) {
							$pref->checked = true;
						}

						$this->prefs_generales->set( $pref );

						// et la pref de modification.
						$id_pref       = $p . '_modif';
						$pref          = new Kintpv_Option_Import();
						$pref->id      = $id_pref;
						$pref->checked = false;

						if ( isset( $_POST[ $id_pref ] ) && $_POST[ $id_pref ] > 0 ) {
							$pref->checked = true;
						}

						$this->prefs_generales->set( $pref );
					}

					$this->save_option( KINTPV_PREFS_GENERALES, $this->prefs_generales->serialize() );

					// Enregistrement des états de commande.

					foreach ( $this->get_order_states() as $key => $value ) {
						$etat          = new Kintpv_Etat_Commande();
						$etat->id      = $key;
						$etat->checked = false;

						if ( isset( $_POST[ 'order_state_' . $key ] ) ) {
							if ( 'on' === $_POST[ 'order_state_' . $key ] ) {
								$etat->checked = true;
							}
						}

						$this->prefs_etat_commande->set( $etat );
					}

					$this->save_option( KINTPV_PREF_ETAT_COMMANDE, $this->prefs_etat_commande->serialize() );

					// Enregistrement de la préférence du nombre de commandes par synchro.

					$synchro_commande        = new Kintpv_Synchro_Commande();
					$synchro_commande->id    = 'orders_by_sync';
					$synchro_commande->value = isset( $_POST['orders_by_sync'] ) ? $_POST['orders_by_sync'] : KINTPV_DEFAULT_ORDER_BY_SYNC;

					$this->prefs_synchro_commande->set( $synchro_commande );

					$this->save_option( KINTPV_PREF_SYNCHRO_COMMANDE, $this->prefs_synchro_commande->serialize() );

					//Preference produit virtuel oui/non pour ls produits hors stocks
					$nostock = new Kintpv_NoStock_Virtual();
					$nostock->id = 'is_virtual';
					$nostock->checked = ( isset( $_POST['nostock_virtual'] ) && 'on' === $_POST['nostock_virtual'] ) ? true : false;

					$this->prefs_nostock_virtual->set($nostock);

					$this->save_option( KINTPV_PREF_NOSTOCK_VIRTUAL, $this->prefs_nostock_virtual->serialize());
				}
			}

			/**
			 * Méthode save_option
			 *
			 * Mets à jour une option du module
			 *
			 * @param int    $id : Id de l'option.
			 * @param string $value : Nouvelle valeur.
			 */
			protected function save_option( $id, $value ) {
				delete_option( $id );
				add_option( $id, $value );
			}

			/**
			 * Méthode header_xml
			 *
			 * Affiche l'entête du fichier XML retourné à KinTPV
			 *
			 * @param bool $header_xml_sent (default = false) : Si l'entête a déjà été envoyé( true ) on ne le re-affiche pas.
			 */
			protected function header_xml( $header_xml_sent = false ) {
				if ( false == $header_xml_sent ) {
					header( 'Content-type: text/xml' );
					echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>' . "\n";
					echo '<KINTPV>';
					echo '<WC_VERSION>' . $GLOBALS['woocommerce']->version . '</WC_VERSION>';
					echo '<MODULE_VERSION>' .KINTPV_CONNECT_V8_VERSION . '</MODULE_VERSION>';
					echo '<MODE_SYNCHRO>CB</MODE_SYNCHRO>';
				}
			}

			/**
			 * Methode xml_code_error
			 *
			 * Méthode d'impression d'une erreur dans le fichier XML de retour
			 *
			 * @param string $error : Erreur à afficher.
			 * @param string $complement (default = '') : Complément de l'erreur envoyée.
			 */
			protected function xml_code_error( $error, $complement = '' ) {
				return '<ERROR>
					<Id>' . $error . '</Id>
					<Message>' . utf8_encode( $this->error_message( $error ) . ( ( 0 !== $error && '' !== $complement ) ? ' : ' . $complement : '' ) ) . '</Message>
				</ERROR>';
			}

			/**
			 * Méthode error_message
			 *
			 * Retourne un message d'erreur en fonction de l'id reçu
			 *
			 * @param int $id : Id de l'erreur reçue.
			 */
			protected function error_message( $id ) {
				$tab_message                                          = array();
				$tab_message[ KINTPV_ERROR_NO_FILE ]                  = 'Fichier XML introuvable';
				$tab_message[ KINTPV_ERROR_FILE_CREATION ]            = 'Impossible d\'enregistrer le fichier';
				$tab_message[ KINTPV_ERROR_FILE_ERROR ]               = 'Impossible d\'ouvrir le fichier';
				$tab_message[ KINTPV_ERROR_IMAGE_CREATION ]           = 'Impossible d\'enregistrer l\'image';
				$tab_message[ KINTPV_ERROR_IMAGE_RESIZE ]             = 'Impossible de redimensionner l\'image';
				$tab_message[ KINTPV_ERROR_PRODUCT_UNAVAILABLE ]      = 'Produit introuvable';
				$tab_message[ KINTPV_ERROR_IMAGE_TOO_LARGE ]          = 'Image trop volumineuse';
				$tab_message[ KINTPV_ERROR_CONFIG ]                   = 'Erreur de configuration';
				$tab_message[ KINTPV_ERROR_SYNCHRO_NOT_CONFIG ]       = 'Le mode de synchronisation n\'est pas configuré dans le module';
				$tab_message[ KINTPV_ERROR_SECURE_KEY ]               = 'Sécurité non valide';
				$tab_message[ KINTPV_ERROR_VERSION_FILE ]             = 'Version de fichier non valide';
				$tab_message[ KINTPV_ERROR_CATEGORY_UNAVAILABLE ]     = 'Catégorie introuvable';
				$tab_message[ KINTPV_ERROR_TAX_NOT_CONFIGURED ]       = 'La liaison des taxes n\'est pas configurée dans le module';
				$tab_message[ KINTPV_ERROR_LANGUAGE ]                 = 'La langue d\'import n\'a pas été configurée';
				$tab_message[ KINTPV_ERROR_MANUFACTURER_UNAVAILABLE ] = 'Marque introuvable';
				$tab_message[ KINTPV_ERROR_FOLDER_CREATION ]          = 'Impossible de créer les dossiers sur le serveur';
				$tab_message[ KINTPV_PRODUCT_PRICE_ERROR ]            = 'Prix négatif sur le produit ';
				$tab_message[ KINTPV_PRODUCT_EAN13_ERROR ]            = 'Code barre non compatible avec WooCommerce ';
				$tab_message[ KINTPV_ERROR_GET_ORDERS ]               = 'Impossible de récupérer les commandes';
				$tab_message[ KINTPV_ERROR_GET_RETURNS ]              = 'Impossible de récupérer les retours';

				if ( isset( $tab_message[ $id ] ) ) {
					return $tab_message[ $id ];
				} else {
					return '';
				}
			}

			/**
			 * Méthode get_order_states
			 *
			 * Retourne le tableau des états des commandes WooCommerce
			 */
			protected function get_order_states() {
				$array = array(
					'pending'    => 'Attente paiement',
					'processing' => 'En cours',
					'on-hold'    => 'En attente',
					'completed'  => 'Terminée',
					'cancelled'  => 'Annulée',
					'refunded'   => 'Remboursée',
					'failed'     => 'Échouée',
				);

				return $array;
			}

			/**
			 * Méthode get_default_order_states
			 *
			 * Retourne les états des commandes par défaut du module
			 *
			 * @param string $state (default = '') : Non d'un état précis à récupérer.
			 */
			protected function get_default_order_states( $state = '' ) {
				$array = array(
					'pending'    => false,
					'processing' => true,
					'on-hold'    => true,
					'completed'  => true,
					'cancelled'  => true,
					'refunded'   => true,
					'failed'     => false,
				);

				if ( '' !== $state ) {
					if ( isset( $array[ $state ] ) ) {
						return $array[ $state ];
					} else {
						return false;
					}
				} else {
					return $array;
				}
			}

			/**
			 * Réalise l'import des préférences, catégories, et des produits depuis KinTPV
			 */
			protected function import_xml() {
				set_time_limit( 0 );
				$this->start_time = microtime( true );
				$this->header_xml();
				$this->check_timer_send();

				Kintpv_Log::log( '--------------------- DEPART ------------------------' );
				Kintpv_Log::log( 'Version WooConnect : ' . KINTPV_CONNECT_V8_VERSION );
				Kintpv_Log::log( 'Version PHP : ' . phpversion() );

				if ( isset( $_SERVER['REQUEST_URI'] ) ) {
					Kintpv_Log::log( 'Request : ' . wp_kses_post( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
				}

				$this->charger_prefs();

				$xml_doc = new DOMDocument();
				$dir     = plugin_dir_path( __FILE__ );

				// récupération du fichier posté.
				$postdata = file_get_contents( 'php://input' );

				$this->error = KINTPV_ERROR_NO_FILE;

				if ( ! empty( $postdata ) ) {
					/* save the file into import folder */
					$file_import = $this->save_xml( $postdata );

					if ( ! $file_import ) {
						$this->error = KINTPV_ERROR_FILE_CREATION;
					} else {
						$this->error = 0;

						Kintpv_Log::log( 'Import du fichier : ' . $file_import );

						$xml_doc->load( $file_import );
						if ( $xml_doc ) {
							$document = $xml_doc->documentElement;
							if ( $document ) {
								$this->import_prefs( $document );
								$this->check_timer_send();

								$this->import_categories( $document );
								$this->check_timer_send();

								$this->import_products( $document );
								$this->check_timer_send();
							} else {
								$this->error = KINTPV_ERROR_FILE_ERROR;
								Kintpv_Log::log( 'Erreur d\'import du fichier : ' . KINTPV_ERROR_FILE_ERROR );
							}
						} else {
							$this->error = KINTPV_ERROR_FILE_ERROR;
							Kintpv_Log::log( 'Erreur d\'import du fichier : ' . KINTPV_ERROR_FILE_ERROR );
						}
						// il faut déplacer le fichier dans les réussis.
						$dossier_xml = plugin_dir_path( __FILE__ ) . KINTPV_XML_PATH . '/' . KINTPV_XML_OK;
						$erreur      = Kintpv_Tools::creer_dossier( $dossier_xml );

						if ( 0 === $erreur ) {
							rename( $file_import, $dossier_xml . '/' . basename( $file_import ) );
						} else {
							$this->error = $erreur;
						}
					}
				}
				$this->termine();
			}

			/**
			 * Méthode check_timer_send
			 *
			 * Vérifie le temps depuis la dernière vérification de l'exécution, pour envoyer à KinTPV que le travai est toujours en cours
			 */
			protected function check_timer_send() {
				$this->end_time = microtime( true );

				if ( $this->end_time - $this->start_time > 1 ) {
					$this->start_time = microtime( true );
					$this->flush();
				}
			}

			/**
			 * Méthode get_xml_path
			 *
			 * Retourne le chemin où enregistrer les fichiers XML reçus
			 */
			protected function get_xml_path() {
				$dir = plugin_dir_path( __FILE__ );
				
				// chemin ou sont enregistrés les fichiers reçus.
				return $dir . KINTPV_XML_PATH;
			}

			/**
			 * Méthode save_xml
			 *
			 * Méthode d'enregistrement d'un fichier XML dans le dossier de synchro
			 *
			 * @param string $content : Contenu du fichier à enregistrer.
			 */
			protected function save_xml( &$content ) {
				$filename = false;
				// chemin ou sont enregistrés les fichiers reçus.
				$dossier_xml = $this->get_xml_path();

				// création du dossier.
				$erreur = Kintpv_Tools::creer_dossier( $dossier_xml );

				if ( 0 !== (int) $erreur ) {
					return $erreur;
				}

				// nettoyage du dossier ( enleve les vieux fichiers ).
				$this->clean_xml_folder();

				$decompresser = false;
				if ( isset( $_SERVER['CONTENT_TYPE'] ) ) {
					switch ( $_SERVER['CONTENT_TYPE'] ) {
						case 'application/zip':
							$ext          = KINTPV_EXT_ZIP;
							$decompresser = true;
							break;

						default:
							$ext = KINTPV_EXT_XML;
					}
				} else {
					$ext = KINTPV_EXT_XML;
				}

				// enregistrement du fichier dans le dossier.
				$filename = $dossier_xml . '/kintpv_' . gmdate( 'YmdHis' ) . $ext;
				$fd       = fopen( $filename, 'w' );

				if ( $fd ) {
					$bytes = fwrite( $fd, $content );

					if ( $decompresser ) {
						if ( '' === $this->extract_zip( $filename ) ) {
							$filename = false;
						}
					}
				} else {
					$filename = '';
				}
				return $filename;
			}

			/**
			 * Méthode extract_zip
			 *
			 * Extrait le contenu d'un fichier zip
			 *
			 * @param string $filename : Nom du fichier à extraire.
			 */
			protected function extract_zip( $filename ) {
				$ext = Tools::strtolower( Tools::substr( $filename, -4 ) );
				// extract file only if extension is zip.
				if ( KINTPV_EXT_ZIP === $ext ) {
					$file_to_extract = $filename;
					// vide le nom de fichier, si l'extraction echoue on retourne un nom vide.
					$filename = '';
					$path     = dirname( $filename );

					// extract zip.
					$dir = $path . '/';
					$zip = new ZipArchive();
					if ( true === $zip->open( $file_to_extract ) ) {
						$arr_ziped_files = array();
						for ( $i = 0; $i < $zip->numFiles; $i++ ) {
							if ( KINTPV_EXT_XML === Tools::strtolower( Tools::substr( $zip->getNameIndex( $i ), -4 ) ) ) {
								$arr_ziped_files[] = $zip->getNameIndex( $i );
							}
						}
						if ( count( $arr_ziped_files ) > 0 ) {
							$zip->extractTo( $dir, $arr_ziped_files[0] );
						}
						$zip->close();
						// delete zip file.
						unlink( $file_to_extract );

						// set the first xml file to the return filename.
						if ( count( $arr_ziped_files ) > 0 ) {
							$filename = $path . '/' . $arr_ziped_files[0];
						}
					}
				}
				return $filename;
			}

			/**
			 * Methode clean_xml_folder
			 *
			 * Supprime tous les fichiers xml dans le dossier de synchro
			 *
			 * @param string $path (default = KINTPV_XML_PATH) : Chemin du dossier des fichiers XML.
			 */
			protected function clean_xml_folder( $path = KINTPV_XML_PATH ) {
				$dir        = plugin_dir_path( __FILE__ );
				$path       = $dir . $path; // chemin ou sont enregistrés les fichiers reçus.
				$name       = $path;
				$path_trash = $path . '/' . KINTPV_XML_TRASH;

				// creation du dossier poubelle.
				$erreur = Kintpv_Tools::creer_dossier( $path_trash );
				if ( 0 !== $erreur ) {
					return $erreur;
				}

				if ( is_dir( $path ) ) {
					if ( opendir( $path ) === $dir ) {
						while ( $file = readdir( $dir ) ) {
							if ( ( ! is_dir( $path . '/' . $file ) ) && '.' !== $file && '..' !== $file ) {
								rename( $path . '/' . $file, $path_trash . '/' . $file );
							}
						}
						closedir( $dir );
					}
				}

				// Suppression des vieux fichiers.
				$this->flush_xml_files( $path );
			}

			/**
			 * Methode flush_xml_files
			 *
			 * Vide le dossier des fichiers XML datant de plus d'une semaine
			 *
			 * @param string $path (default = KINTPV_XML_PATH) : Chemin du dossier des fichiers XML.
			 */
			protected function flush_xml_files( $path = KINTPV_XML_PATH ) {
				$folder = $path . '/' . KINTPV_XML_OK;
				// recherche des fichiers dans le repertoire.

				if ( is_dir( $folder ) ) {
					$folder .= '/';
					$dir     = opendir( $folder );
					if ( null !== $dir ) {
						while ( ( $file = readdir( $dir ) ) ) {
							$ext = strtolower( substr( $file, -4 ) );

							// accepte uniquement les XML et les ZIP.
							if ( KINTPV_EXT_ZIP === $ext || KINTPV_EXT_XML === $ext ) {
								// if file was sent more than 1 week, we delete it.
								if ( filemtime( $folder . $file ) < ( time() - ( 3600 * 24 * NB_JOUR_FLUSH ) ) ) {
									unlink( $folder . $file );
								}
							}
						}
						closedir( $dir );
					}
				}
			}

			/**
			 * Méthode import_image
			 *
			 * Importe une image dans WooCommerce
			 */
			protected function import_image() {
				set_time_limit( 0 );
				$this->header_xml();

				$this->charger_prefs();
				$error = 0;
				Kintpv_Log::log( '--------------------- DEPART ------------------------' );
				Kintpv_Log::log( 'Version WooConnect : ' . KINTPV_CONNECT_V8_VERSION );
				if ( isset( $_SERVER['REQUEST_URI'] ) ) {
					Kintpv_Log::log( 'Request : ' . wp_kses_post( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
				}

				$type = 'ARTICLE';

				if ( isset( $_GET['type'] ) ) {
					$type = wp_kses_post( wp_unslash( $_GET['type'] ) );
				}

				$is_critere = ( false !== strpos( $type, 'CRITERE_' ) ) ? true : false;

				if ( ! $is_critere ) {
					Kintpv_Log::log( 'Import image type : ' . $type );
					switch ( $type ) {
						case 'CATEGORIE':
							$error = $this->import_image_categorie();
							break;
						default:
							$error = $this->import_image_produit();
							break;
					}
				} else {
					Kintpv_Log::log( "Image de critère : Pas d'import." );
				}

				$this->error = $error;

				$this->termine();

				return null;
			}

			/**
			 * Methode get_chemin_content
			 *
			 * Retroune le chemin du dossier des contenus de WordPress (wp-content)
			 */
			protected function get_chemin_content() {
				$url_base       = get_site_url();
				$chemin_theme   = get_theme_root();
				$chemin_content = '';
				// puis on remonte le chemin.
				// recherche du dernier "/".
				$last_slash = strrpos( $chemin_theme, '/' );
				if ( $last_slash ) {
					// recherche le suivant :.
					$prec_slash = strrpos( $chemin_theme, '/', ( 1 + strlen( $chemin_theme ) - $last_slash ) * -1 );

					$chemin_content = $url_base . '/' . substr( $chemin_theme, $prec_slash + 1, $last_slash - $prec_slash );
				}

				return $chemin_content;
			}

			/**
			 * Methode import_image_produit
			 *
			 * Importe une image dans un produit WooCommerce
			 */
			protected function import_image_produit() {
				$error   = 0;
				$product = null;
				// avant tout, il faut retrouver l'article.
				if ( isset( $_GET['codebarre'] ) ) {
					$art = $this->get_product( $_GET['codebarre'] );

					$images_art = $this->get_images_produit( $art[0]->ID );

					if ( ! empty( $art ) ) {
						Kintpv_Log::log( 'Article trouvé : ' . $art[0]->ID );
						if ( 'product_variation' == $art[0]->post_type ) {
							$product = $this->get_product_by_variation( $art[0]->ID );
						}
						// le produit contient il des images.
						if ( count( $images_art ) == 0 ) {
							$creation = true;
						} else {
							$creation = false;
						}

						if ( $this->option_checked( 'img_prod', $creation ) ) {
							$chemin_content = $this->get_chemin_content();

							if ( '' != $chemin_content ) {
								$postdata = file_get_contents( 'php://input' );
								$filename = '';
								$error    = $this->save_tmp_image( $postdata, $filename, $chemin_complet );
								$name = explode('tmp/',$filename)[1];
								
								if ( 0 == $error ) {
									// si nous recevons la premiere image, on vide tout.
									if ('product' == $art[0]->post_type) {
										if ( 1 == $this->num ) {
											$article['images'] = array();
											$this->update_product( $art[0]->ID, $article );
											
											$this->reset_images_produit($art[0]->ID);
										} else {
											$article['images'] = $images_art;
										}
									}
									
									$filename = $chemin_content . 'plugins/kintpv-connect/' . $filename;
									Kintpv_Log::log( 'Mise à jour du produit avec l\'image : ' . $filename );
									
									$article['images'][] = array(
										'src'      => $filename,
										'name'     => $name,
										'position' => isset( $_GET['num'] ) ? (int) $_GET['num'] - 1 : 0,
									);
									
									if ( 'product_variation' == $art[0]->post_type ) {
										$data          = array();
										$data['image'] = array(
											'src'      => $filename,
											'name'     => $name,
											'position' => isset( $_GET['num'] ) ? (int) $_GET['num'] - 1 : 0,
										);
										
										$this->update_variation($product->ID, $art[0]->ID, $data);
									} else {
										$this->update_product( $art[0]->ID, $article );
									}

									// destruction de l'image temporaire.
									$unlink = preg_replace('/([^:])(\/{2,})/', '$1/', $chemin_complet);
									if ( is_file($unlink) ) {
										unlink( $unlink );
									}
								} else {
									Kintpv_Log::log( 'IMAGE ERREUR : Impossible d\enregistrer l\image. Code erreur : ' . $error );
								}
							} else {
								Kintpv_Log::log( 'ERREUR : Impossible d\'identifier le chemin temporaire des images' );
							}
						} else {
							Kintpv_Log::log( 'IMAGE : Mise à jour non permise par la configuration du module' );
						}
					} else {
						Kintpv_Log::log( 'IMAGE ERREUR : Aucun article correspondant au code barre reçu : ' . wp_kses_post( wp_unslash( $_GET['codebarre'] ) ) . ' => ' . print_r( $art, true ) );
					}
				} else {
					Kintpv_Log::log( 'IMAGE ERREUR : Aucun code barre reçu' );
				}

				return $error;
			}

			/**
			 * Supprime les images du produit
			 * 
			 * @param int $id : Id du produit à supprimer
			 */
			public function reset_images_produit($id)
			{
				
				$product_type = WC_Product_Factory::get_product_type($id);
				
				if ($product_type === 'simple') {
					$product = new WC_Product($id);
				} else {
					$product = new WC_Product_Variable($id);
				}

				$product->set_image_id(0);
				$product->set_gallery_image_ids([]);
				$product->save();
			}

			/**
			 * Méthode import_image_categorie
			 *
			 * Improte une image dans une catégorie WooConnecte
			 */
			protected function import_image_categorie() {
				if ( isset( $_GET['nom'] ) ) {
					// Récpération du nom de la catégorie pour la transformer en slug.
					$cat = $this->get_categorie( 0, $this->slugify( wp_kses_post( wp_unslash( $_GET['nom'] ) ) ) );

					if ( $cat && isset( $cat[0]->id ) ) {
						$id_wc = $cat[0]->id;

						Kintpv_Log::log( 'Catégorie trouvée : ' . $id_wc );
						// le produit contient il des images.
						if ( ! $cat[0]->image ) {
							$creation = true;
						} else {
							$creation = false;
						}

						if ( $this->option_checked( 'img_cat', $creation ) ) {
							$chemin_content = $this->get_chemin_content();

							if ( '' != $chemin_content ) {
								$postdata = file_get_contents( 'php://input' );
								$filename = '';
								$error    = $this->save_tmp_image( $postdata, $filename, $chemin_complet );

								if ( 0 === $error ) {
									$filename = $chemin_content . 'plugins/kintpv-connect/' . $filename;
									Kintpv_Log::log( 'Mise à jour de la catégorie avec l\'image : ' . $filename );

									$categorie           = array();
									$categorie['image']  = array();
									$categorie['image']  = array( 'src' => $filename );
									$categorie['name']   = wp_kses_post( wp_unslash( $_GET['nom'] ) );
									$categorie['id']     = $cat[0]->id;
									$categorie['id_kin'] = isset( $_GET['id'] ) ? wp_kses_post( wp_unslash( $_GET['id'] ) ) : 0;
									$categorie['slug']   = $this->slugify( wp_kses_post( wp_unslash( $_GET['nom'] ) ) );

									$this->update_categorie( $id_wc, $categorie );

									// destruction de l'image temporaire.
									unlink( $chemin_complet );
								} else {
									Kintpv_Log::log( 'IMAGE ERREUR : Impossible d\enregistrer l\image. Code erreur : ' . $error );
								}
							} else {
								Kintpv_Log::log( 'ERREUR : Impossible d\'identifier le chemin temporaire des images' );
							}
						} else {
							Kintpv_Log::log( 'IMAGE : Mise à jour non permise par la configuration du module' );
						}
					} else {
						Kintpv_Log::log( 'IMAGE : Catégorie introuvable' );
					}
				} else {
					Kintpv_Log::log( 'IMAGE : Id catégorie non transmis' );
				}

				return $error;
			}

			/**
			 * Méthode get_images_produit
			 *
			 * Retourne les images d'un produit
			 *
			 * @param int $sku : Id du produit.
			 */
			protected function get_images_produit( $sku ) {
				global $wpdb;

				// La requête récupère le post_id des enregsitrements ayant pour "nom" ( meta_key ) '_thumbnail_id' correspondant.
				// au post de l'image.
				// On récupère donc en meme temps le wp_post correspondant.

				$images = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * from {$wpdb->prefix}postmeta right join {$wpdb->prefix}posts on {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.meta_value
						where {$wpdb->prefix}postmeta.meta_key = '_thumbnail_id' and {$wpdb->prefix}postmeta.post_id = %d",
						$sku
					)
				);

				$array_images = array();

				if ( ! empty( $images ) ) {
					foreach ( $images as $img ) {
						$arr_tmp = array(
							'id'                => $img->ID,
							'date_created'      => $img->post_date,
							'date_created_gmt'  => $img->post_date_gmt,
							'date_modified'     => $img->post_modified,
							'date_modified_gmt' => $img->post_modified_gmt,
							'src'               => $img->guid,
							'name'              => $img->post_name,
							'alt'               => '',
						);

						$array_images[] = $arr_tmp;
					}
				}

				return $array_images;
			}

			/**
			 * Méthode save_tmp_image
			 *
			 * Enregistre une image dans un dossier temporaire avant l'import dans WooCommerce
			 *
			 * @param mixed  $data : Contenu reçu dans la requête (l'image).
			 * @param string $filename : Nom du fichier.
			 * @param string $chemin_complet : Chemin d'accès au fichier.
			 */
			protected function save_tmp_image( &$data, &$filename, &$chemin_complet ) {
				$erreur = 0;

				// create tmp folder.
				$dir     = plugin_dir_path( __FILE__ );
				$dossier = $dir . KINTPV_TMP_IMG;

				$erreur = Kintpv_Tools::creer_dossier( $dossier );

				if ( 0 !== $erreur ) {
					return $erreur;
				}

				// save image in tmp file.
				$filename = KINTPV_TMP_IMG . '/' . gmdate( 'YmdHis' ) . random_int(1, 1000) . '.jpg';

				$chemin_complet = $dir . '/' . $filename;
				$fd             = fopen( $dir . $filename, 'w' );

				if ( $fd ) {
					fwrite( $fd, $data );
					fclose( $fd );
				} else {
					$erreur = KINTPV_ERROR_IMAGE_CREATION;
				}

				return $erreur;
			}

			/**
			 * Methode upload_image
			 *
			 * Envoi d'une image dans les fichiers de contenu de WordPress
			 *
			 * @param string $path : Chemin de destination du fichier.
			 */
			public function upload_image( $path ) {
				$request_url = 'http://localhost/wordpress/wordpress/wp/v2/media';
				$image       = file_get_contents( $path );
				$mime_type   = mime_content_type( $path );
				$api         = curl_init();

				// set the url, POST data.
				curl_setopt( $api, CURLOPT_URL, $request_url );

				curl_setopt( $api, CURLOPT_POST, 1 );

				curl_setopt( $api, CURLOPT_POSTFIELDS, $image );

				curl_setopt( $api, CURLOPT_HTTPHEADER, array( 'Content-Type: ' . $mime_type, 'Content-Disposition: attachment; filename="' . basename( $path ) . '"' ) );

				curl_setopt( $api, CURLOPT_RETURNTRANSFER, 1 );

				curl_setopt( $api, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );

				// Execute post.
				$result = curl_exec( $api );

				// Close connection.
				curl_close( $api );

				return json_decode( $result );
			}

			/**
			 * Methode save_art_image
			 *
			 * Enregistre une image d'un article
			 *
			 * @param mixed  $data : Image reçue dans la requête.
			 * @param string $filename : Nom du fichier.
			 * @param string $chemin_complet : Chemin de destination de l'image.
			 * @param string $nom : Nom de l'image.
			 */
			protected function save_art_image( &$data, &$filename, &$chemin_complet, $nom ) {
				$erreur = 0;

				// create tmp folder.
				$dir     = plugin_dir_path( __FILE__ );
				$dossier = $dir . KINTPV_IMG_ART;

				$erreur = Kintpv_Tools::creer_dossier( $dossier );
				if ( 0 !== $erreur ) {
					return $erreur;
				}

				// save image in tmp file.
				$filename       = $dossier . '/' . $nom . '.jpg';
				$chemin_complet = $dir . '/' . $filename;
				$fd             = fopen( $dir . '/' . $filename, 'w' );

				if ( $fd ) {
					fwrite( $fd, $data );
					fclose( $fd );
				} else {
					$erreur = KINTPV_ERROR_IMAGE_CREATION;
				}

				return $erreur;
			}

			/**
			 * Methode export_cmd
			 *
			 * Methode de retour des commandes à KinTPV
			 */
			protected function export_cmd() {
				$this->header_xml();
				set_time_limit( 0 );

				$this->charger_prefs();

				$xml     = '';
				$refunds = array();

				Kintpv_Log::log( '--------------------- DEPART ------------------------' );
				Kintpv_Log::log( 'Version WooConnect : ' . KINTPV_CONNECT_V8_VERSION );
				Kintpv_Log::log( 'Export de commandes - Id reçu de KinTPV : ' . ( $this->last_cmd_received ) . '' );
				Kintpv_Log::log( 'Export de commandes - Recherche à partir de la commande #' . ( $this->last_cmd_received ) );

				$commandes = (array) $this->get_commandes( (int) $this->last_cmd_received );

				Kintpv_Log::log( 'Commandes récupérées : ' . count( $commandes ) );
				$refunds = $this->get_refunds();

				$wc_transporteurs = $this->shipping_list();

				foreach ( $commandes as $c ) {
					$to_array = function ( $x ) use ( &$to_array ) {
						return is_scalar( $x )
						? $x
						: array_map( $to_array, (array) $x );
					};

					$c = (array) $to_array( $c );

					Kintpv_Log::log( 'Récupération de la commande WC #' . $c['id'] );
					$code_client = ( '0' !== $c['customer_id'] ) ? 'w' . $c['customer_id'] : 'wg' . $c['id'];

					$xml .= '<VENTE>' . PHP_EOL;
					$xml .= $this->genere_balise_xml( 'VT_CodeOrigineVente', '' ); // pour la gestion des retours.
					$xml .= $this->genere_balise_xml( 'VT_NumUniqueWeb', $c['id'] ); // id WC de la commande.
					$xml .= $this->genere_balise_xml( 'VT_MontantVente', $c['total'] ); // montant total de la commande.
					$xml .= $this->genere_balise_xml( 'VT_CodeClient', $code_client ); // id client ( précédé de w pour web ).
					$xml .= $this->genere_balise_xml( 'VT_Date', $c['date_created'] ); // date de la commande.
					$xml .= $this->genere_balise_xml( 'VT_Heure', substr( $c['date_created'], 11, 8 ) );// heure de la commande.
					$xml .= $this->genere_balise_xml( 'LV_Nom', $c['shipping']['first_name'] . ' ' . $c['shipping']['last_name'] ); // nom de livraison.
					// generation de l'adresse de livraison.
					$adresse = $c['shipping']['address_1'];

					if ( '' !== $c['shipping']['address_2'] ) {
						$adresse .= "\n" . $c['shipping']['address_2'];
					}

					$adresse .= "\n" . $c['shipping']['postcode'] . ' ' . $c['shipping']['city'] . "\n" . $c['shipping']['country'];

					$xml .= $this->genere_balise_xml( 'LV_Adresse', $adresse );// adresse.
					$xml .= $this->genere_balise_xml( 'LV_Firstname', $c['shipping']['first_name'] );// prénom.
					$xml .= $this->genere_balise_xml( 'LV_Lastname', $c['shipping']['last_name'] );// nom.
					$xml .= $this->genere_balise_xml( 'LV_Adresse1', $c['shipping']['address_1'] );// adresse 1.
					$xml .= $this->genere_balise_xml( 'LV_Adresse2', (string) $c['shipping']['address_2'] );// adresse 2.
					$xml .= $this->genere_balise_xml( 'LV_CodePostal', $c['shipping']['postcode'] );// code postal.
					$xml .= $this->genere_balise_xml( 'LV_Ville', $c['shipping']['city'] );// ville.
					$xml .= $this->genere_balise_xml( 'LV_Pays', $c['shipping']['country'] ); // pays.
					$xml .= $this->genere_balise_xml( 'LV_Complement', $c['shipping']['company'] );// complément d'adresse.

					// les informations client.
					$xml .= $this->genere_balise_xml( 'CLI_Civilite', '' );// la civilité n'existe pas.
					$xml .= $this->genere_balise_xml( 'CLI_Prenom', $c['billing']['first_name'] );// prénom.
					$xml .= $this->genere_balise_xml( 'CLI_Nom', $c['billing']['last_name'] );// nom.
					$xml .= $this->genere_balise_xml( 'CLI_Societe', $c['billing']['company'] );// société.
					$xml .= $this->genere_balise_xml( 'CLI_Adr1', $c['billing']['address_1'] );// adresse 1.
					$xml .= $this->genere_balise_xml( 'CLI_Adr2', $c['billing']['address_2'] );// adresse 2.
					$xml .= $this->genere_balise_xml( 'CLI_CodePostal', $c['billing']['postcode'] );// code postal.
					$xml .= $this->genere_balise_xml( 'CLI_Ville', $c['billing']['city'] );// ville.
					$xml .= $this->genere_balise_xml( 'CLI_IdPays', 0 );// Pays ??? je n'ai pas d'ID... je n'ai qu'un libellé.
					$xml .= $this->genere_balise_xml( 'CLI_TelM', $c['billing']['phone'] );// Je n'ai qu'un téléphone.
					$xml .= $this->genere_balise_xml( 'CLI_Email', $c['billing']['email'] );// l'email.
					$xml .= $this->genere_balise_xml( 'VT_Commentaire', $c['customer_note'] );// commentaire du client.

					// infos générales de la commande.
					$xml .= $this->genere_balise_xml( 'VT_NbDetails', count( $c['line_items'] ) + count( $c['shipping_lines'] ) + 1 ); // nombre de lignes + transports + 1 ( pour le paiement ).

					$vt_etat = false;

					if ( false !== $this->prefs_etat_commande->get( $c['status'] ) ) {
						$vt_etat = $this->prefs_etat_commande->get( $c['status'] )->checked;
					} else {
						$vt_etat = $this->get_default_order_states( $c['status'] );
					}

					$xml .= $this->genere_balise_xml( 'VT_Etat', ( true === $vt_etat ) ? '2' : '1' ); // commande validée ou non.

					// le règlement.
					$xml         .= '<REGLEMENT>' . PHP_EOL;
					$id_reglement = $this->link_reglements->get_value( $c['payment_method'], 'id_kintpv' ); // +~0 POUR 10.
					$xml         .= $this->genere_balise_xml( 'RG_IdTypeReglement', $id_reglement ? $id_reglement : '250' ); // mode de paiement utilisé.
					$xml         .= $this->genere_balise_xml( 'RG_Libelle', $c['payment_method_title'] ); // son libellé.
					$xml         .= $this->genere_balise_xml( 'RG_Montant', $c['total'] ); // le montant ( je n'ai pas le montant du paiement mais uniquement le montant de la commande ).
					$xml         .= '</REGLEMENT>' . PHP_EOL;

					// les details.
					// on commence par le mode de transport.
					foreach ( $c['shipping_lines'] as $s ) {
						$xml .= '<DETAIL>' . PHP_EOL;
						$xml .= $this->genere_balise_xml( 'VT_Type', 'PORT' ); // le type est en dur.

						// il faut retrouver la ligne de frais de port dans le rapprochement.
						$tab_method = explode( ':', $s['method_id'] );
						if ( $tab_method && count( $tab_method ) === 2 ) {
							$method_id = $tab_method[1];
						} else {
							$method_id = $s['method_id'];
						}

						$w_id_transporteur = '';

						foreach ( $wc_transporteurs as $z ) {
							if ( count( $z['methods'] ) > 0 ) {
								foreach ( $z['methods'] as $t ) {
									if ( $method_id === $t->method_id && '' === $w_id_transporteur ) {
										$w_id_transporteur = $t->id;
									}
								}
							}
						}

						$id_transporteur = $this->link_transporteurs->get_value( $w_id_transporteur, 'id_kintpv' ); // +~0 POUR 10

						$ref      = '';
						$barecode = '';

						if ( ! $id_transporteur ) {
							$xml .= $this->genere_balise_xml( 'VT_Error', 'Transporteur "wc_id = ' . $method_id . '", nom : "' . $s['method_title'] . '" lié à aucun transporteur KinTPV' );
						} else {
							$port     = $this->prefs_transporteur->get( $id_transporteur );
							$ref      = $port->ref;
							$barecode = $port->barecode;
						}

						$xml .= $this->genere_balise_xml( 'VT_IdArticle', $id_transporteur ? $id_transporteur : '0' ); // mode de paiement utilisé.
						$xml .= $this->genere_balise_xml( 'VT_Qte', '1' ); // la quantité ( toujours 1 ).
						$xml .= $this->genere_balise_xml( 'VT_Reference', $ref ); // reference de la livraison.
						$xml .= $this->genere_balise_xml( 'VT_CodeBarre', $barecode ); // code barre de la livraison.
						$xml .= $this->genere_balise_xml( 'VT_PrixVente', $s['total'] + $s['total_tax'] ); // montant.

						$taux = 0;

						$id_tva = 0;
						if ( $s['taxes'] ) {
							$id_tva = $this->prefs_taxe->search( 'wc_id', $s['taxes'][0]['id'] );
							$taux   = $this->get_taux_taxe( $s['taxes'][0]['id'] );
						}

						$xml .= $this->genere_balise_xml( 'VT_TauxTaxe', $taux ); // le taux de la tva.
						$xml .= $this->genere_balise_xml( 'VT_IdTauxTaxe', $id_tva ); // l'id de la tva KinTPV.
						$xml .= '</DETAIL>' . PHP_EOL;
					}

					// il faut mettre tous les details de commande.
					foreach ( $c['line_items'] as $l ) {
						$xml       .= '<DETAIL>' . PHP_EOL;
						$xml       .= $this->genere_balise_xml( 'VT_Type', 'ARTICLE' );// Le type est en dur.
						$id_article = 0;

						$xml .= $this->genere_balise_xml( 'VT_IdArticle', $l['product_id'] );
						$xml .= $this->genere_balise_xml( 'VT_NomArticle', $l['name'] );
						$xml .= $this->genere_balise_xml( 'VT_Qte', $l['quantity'] );
						$xml .= $this->genere_balise_xml( 'VT_Reference', $l['reference'] );
						$xml .= $this->genere_balise_xml( 'VT_CodeBarre', $l['sku'] );
						$xml .= $this->genere_balise_xml( 'VT_PrixVente', ( ( $l['total'] / $l['quantity'] ) + ( $l['total_tax'] / $l['quantity'] ) ) );

						$taux   = 0;
						$id_tva = 0;

						if ( $l['taxes'] ) {
							$rate_id = $l['taxes'][0]['id'];
							$id_tva  = $this->prefs_taxe->search( 'wc_id', $rate_id );
							$taux    = $this->get_taux_taxe( $rate_id );
						}

						$xml .= $this->genere_balise_xml( 'VT_TauxTaxe', $taux );
						$xml .= $this->genere_balise_xml( 'VT_IdTauxTaxe', $id_tva );
						$xml .= '</DETAIL>' . PHP_EOL;
					}

					$xml .= '</VENTE>' . PHP_EOL;
				}

				// et la liste des retours / remboursements.
				// ------------------------------------------------.
				foreach ( $refunds as $c ) {
					$to_array = function ( $x ) use ( &$to_array ) {
						return is_scalar( $x )
						? $x
						: array_map( $to_array, (array) $x );
					};

					$c = (array) $to_array( $c );

					$code_client = ( 0 !== (int)$c['order']['customer_id'] ) ? 'w' . $c['order']['customer_id'] : 'wg' . $c['order']['id'];

					$xml .= '<VENTE>' . PHP_EOL;
					$xml .= $this->genere_balise_xml( 'VT_CodeOrigineVente', K_ORIGINE_RETOUR );// pour la gestion des retours.
					$xml .= $this->genere_balise_xml( 'VT_NumUniqueWeb', $c['id'] );// id WC de la commande.
					$xml .= $this->genere_balise_xml( 'VT_MontantVente', 0 );// montant total de la commande.
					$xml .= $this->genere_balise_xml( 'VT_CodeClient', $code_client );// id client ( précédé de w pour web ).
					$xml .= $this->genere_balise_xml( 'VT_Date', $c['date_created'] );// date de la commande.
					$xml .= $this->genere_balise_xml( 'VT_Heure', substr( $c['date_created'], 11, 8 ) );// heure de la commande.
					$xml .= $this->genere_balise_xml( 'LV_Nom', $c['order']['shipping']['first_name'] . ' ' . $c['order']['shipping']['last_name'] );// nom de livraison.
					// generation de l'adresse de livraison.
					$adresse = $c['order']['shipping']['address_1'];

					if ( '' !== $c['order']['shipping']['address_2'] ) {
						$adresse .= "\n" . $c['order']['shipping']['address_2'];
					}

					$adresse .= "\n" . $c['order']['shipping']['postcode'] . ' ' . $c['order']['shipping']['city'] . "\n" . $c['order']['shipping']['country'];

					$xml .= $this->genere_balise_xml( 'LV_Adresse', $adresse );// adresse.
					$xml .= $this->genere_balise_xml( 'LV_Firstname', $c['order']['shipping']['first_name'] );// prénom.
					$xml .= $this->genere_balise_xml( 'LV_Lastname', $c['order']['shipping']['last_name'] );// nom.
					$xml .= $this->genere_balise_xml( 'LV_Adresse1', $c['order']['shipping']['address_1'] );// adresse 1.
					$xml .= $this->genere_balise_xml( 'LV_Adresse2', $c['order']['shipping']['address_2'] );// adresse 2.
					$xml .= $this->genere_balise_xml( 'LV_CodePostal', $c['order']['shipping']['postcode'] );// code postal.
					$xml .= $this->genere_balise_xml( 'LV_Ville', $c['order']['shipping']['city'] );// Ville.
					$xml .= $this->genere_balise_xml( 'LV_Pays', $c['order']['shipping']['country'] );// Pays.
					$xml .= $this->genere_balise_xml( 'LV_Complement', $c['order']['shipping']['company'] );// Complément d'adresse.

					// les informations client.
					$xml .= $this->genere_balise_xml( 'CLI_Civilite', '' );// la civilité n'existe pas.
					$xml .= $this->genere_balise_xml( 'CLI_Prenom', $c['order']['billing']['first_name'] );// prénom.
					$xml .= $this->genere_balise_xml( 'CLI_Nom', $c['order']['billing']['last_name'] );// nom.
					$xml .= $this->genere_balise_xml( 'CLI_Societe', $c['order']['billing']['company'] );// société.
					$xml .= $this->genere_balise_xml( 'CLI_Adr1', $c['order']['billing']['address_1'] );// adresse 1.
					$xml .= $this->genere_balise_xml( 'CLI_Adr2', $c['order']['billing']['address_2'] );// adresse 2.
					$xml .= $this->genere_balise_xml( 'CLI_CodePostal', $c['order']['billing']['postcode'] );// code postal.
					$xml .= $this->genere_balise_xml( 'CLI_Ville', $c['order']['billing']['city'] );// ville.
					$xml .= $this->genere_balise_xml( 'CLI_IdPays', 0 );// Pays ??? je n'ai pas d'ID... je n'ai qu'un libellé.
					$xml .= $this->genere_balise_xml( 'CLI_TelM', $c['order']['billing']['phone'] );// Je n'ai qu'un téléphone.
					$xml .= $this->genere_balise_xml( 'CLI_Email', $c['order']['billing']['email'] );// l'email.
					$xml .= $this->genere_balise_xml( 'VT_Commentaire', $c['order']['customer_note'] );// commentaire du client.

					// infos générales de la commande.
					$xml .= $this->genere_balise_xml( 'VT_NbDetails', count( $c['line_items'] ) );// nombre de lignes.

					$vt_etat = false;

					if ( false !== $this->prefs_etat_commande->get( $c['order']['status'] ) ) {
						$vt_etat = $this->prefs_etat_commande->get( $c['order']['status'] )->checked;
					} else {
						$vt_etat = $this->get_default_order_states( $c['order']['status'] );
					}

					$xml .= $this->genere_balise_xml( 'VT_Etat', ( true === $vt_etat ) ? '2' : '1' );// Commande validée ou non.

					// puis on insert les details.
					foreach ( $c['line_items'] as $l ) {
						$xml .= '<DETAIL>' . PHP_EOL;

						$xml .= $this->genere_balise_xml( 'VT_Type', 'ARTICLE' );// Le type est en dur.

						$id_article = 0;
						// il faut rechercher le produit concerné.
						if ( $l['variation_id'] ) {
							$wc_variation = $this->get_product_variation( $l['product_id'], '', $l['variation_id'] );

							if ( is_array( $wc_variation ) && $wc_variation['meta_data'] ) {
								foreach ( $wc_variation['meta_data'] as $m ) {
									if ( KINTPV_META_ID_ARTICLE === $m['key'] ) {
										$id_article = $m['value'];
									}
								}
							}
						} else {
							if ( isset( $wc_prod ) ) {
								if ( $wc_prod['meta_data'] ) {
									foreach ( $wc_prod['meta_data'] as $m ) {
										if ( KINTPV_META_ID_ARTICLE === $m['key'] ) {
											$id_article = $m['value'];
										}
									}
								}
							}
						}

						$xml .= $this->genere_balise_xml( 'VT_IdArticle', $l['product_id'] );

						$xml .= $this->genere_balise_xml( 'VT_NomArticle', $l['name'] );

						$xml .= $this->genere_balise_xml( 'VT_Qte', $l['quantity'] );

						$xml .= $this->genere_balise_xml( 'VT_Reference', '' );

						$xml .= $this->genere_balise_xml( 'VT_CodeBarre', $l['sku'] );
						
						$prix_vente = ($l['total'] + $l['total_tax']);
						
						//Si la valeur est négative, on la rends positive.
						if ( $prix_vente < 0 ) {
							$prix_vente = $prix_vente * -1;
						}
						
						$xml .= $this->genere_balise_xml( 'VT_PrixVente', $prix_vente );

						$taux   = 0;
						$id_tva = 0;

						if ( $l['taxes'] ) {
							$rate_id = $l['taxes'][0]['id'];
							$id_tva  = $this->prefs_taxe->search( 'wc_id', $rate_id );
							$taux    = $this->get_taux_taxe( $rate_id );
						}

						$xml .= $this->genere_balise_xml( 'VT_TauxTaxe', $taux );
						$xml .= $this->genere_balise_xml( 'VT_IdTauxTaxe', $id_tva );
						$xml .= '</DETAIL>' . PHP_EOL;
					}
					$xml .= '</VENTE>' . PHP_EOL;
				}
				Kintpv_Log::log( '--------------------- FIN ------------------------' );

				$xml .= '</KINTPV>';
				echo $xml;
			}

			/**
			 * Methode get_taux_taxe
			 *
			 * Retourne le taux d'une taxe WC
			 *
			 * @param int $id_taxe_wc : Taxe WC concernée.
			 */
			protected function get_taux_taxe( $id_taxe_wc ) {
				if ( ! $this->wc_taxes ) {
					$this->wc_taxes = $this->get_wc_taxes();
				}

				$taux = false;

				$taxes_count = count( $this->wc_taxes );

				for ( $i = 0; $i < $taxes_count && false == $taux; $i++ ) {
					if ( $this->wc_taxes[ $i ]->id == $id_taxe_wc ) {
						$taux = $this->wc_taxes[ $i ]->rate;
					}
				}

				return $taux;
			}

			/**
			 * Methode flush
			 *
			 * Rempli le buffer
			 */
			protected function flush() {
				// envoi 4096 caractere pour complÃ©ter la chaine et remplir le buffer
				@ob_start();
				@ob_flush();
				echo str_pad( '', 4096 );
				flush();
			}

			/**
			 * Methode genere_balise_xml
			 *
			 * Retourne une balise XML dans le bon format
			 *
			 * @param string $nom : Nom de la balise.
			 * @param string $valeur : Contenu de la balise.
			 */
			protected function genere_balise_xml( $nom, $valeur ) {
				return '<' . $nom . '>' . htmlspecialchars( $valeur ) . '</' . $nom . '>' . PHP_EOL;
			}

			/**
			 * Methode import_prefs
			 *
			 * Importe les préférences dans le module
			 *
			 * @param object $document : Contenu envoyé par KinTPV.
			 */
			protected function import_prefs( &$document ) {
				$this->import_tva( $document );
				$this->import_transporteurs( $document );
				$this->import_paiements( $document );

				$pref = $document->getElementsByTagName( 'PREF' );

				// initialise le tableau des critères pour les 12 possibilités.
				$this->tab_criteres = array( '', '', '', '', '', '', '', '', '', '', '', '', '' );
				if ( $pref ) {
					for ( $i = 1; $i <= 12; $i++ ) {
						$this->tab_criteres[ $i ] = self::get_node_value( $pref->item( 0 ), 'CRITERE_' . $i );
					}
				}

				foreach ( $this->tab_criteres as $key => $value ) {
					$critere       = new Kintpv_Critere();
					$critere->id   = 'CRITERE_' . $key;
					$critere->name = $value;

					$this->prefs_criteres_kintpv->set( $critere );
				}

				$this->save_option( KINTPV_PREF_CRITERE, $this->prefs_criteres_kintpv->serialize() );
			}

			/**
			 * Methode import_tva
			 *
			 * Importe les préférences liées aux taxes
			 *
			 * @param object $document : Contenu envoyé par KinTPV.
			 */
			protected function import_tva( &$document ) {
				$tag_liste = $document->getElementsByTagName( 'LISTE_TAUX_TVA' );

				if ( $tag_liste && $tag_liste->length > 0 ) {
					$taxes = $tag_liste->item( 0 )->getElementsByTagName( 'TAUX_TVA' );

					if ( $taxes ) {
						for ( $i = 0; $i < $taxes->length; $i++ ) {
							$id   = self::get_node_value( $taxes->item( $i ), 'IdTauxTVA' );
							$taux = self::get_node_value( $taxes->item( $i ), 'Taux' );

							// update de la liste des taux.
							$taxe = $this->prefs_taxe->get( $id );
							if ( ! $taxe ) {
								$taxe = new Kintpv_Taxe();
							}

							$taxe->id   = $id;
							$taxe->taux = $taux;

							$this->prefs_taxe->set( $taxe );
						}
						// enregistre les paramètres en base.
						delete_option( KINTPV_PREF_TAXES );
						add_option( KINTPV_PREF_TAXES, $this->prefs_taxe->serialize() );
					}
				}
			}

			/**
			 * Méthode import_transporteurs
			 *
			 * Importe les préférences relatives aux transporteurs
			 *
			 * @param object $document : Contenu envoyé par KinTPV.
			 */
			protected function import_transporteurs( &$document ) {
				$tag_liste = $document->getElementsByTagName( 'LISTE_FRAISPORT' );

				if ( $tag_liste && $tag_liste->length > 0 ) {
					$ports = $tag_liste->item( 0 )->getElementsByTagName( 'FRAISPORT' );
					if ( $ports ) {
						for ( $i = 0; $i < $ports->length; $i++ ) {
							$id  = self::get_node_value( $ports->item( $i ), 'IdArticle' );
							$nom = self::get_node_value( $ports->item( $i ), 'NomCatalogue' );

							// update de la liste des transporteurs.
							$port = $this->prefs_transporteur->get( $id );
							if ( ! $port ) {
								$port = new Kintpv_Transporteur();
							}

							$port->id       = $id;
							$port->nom      = $nom;
							$port->ref      = self::get_node_value( $ports->item( $i ), 'ArticleReference' );
							$port->barecode = self::get_node_value( $ports->item( $i ), 'ArticleCodeBarre' );

							$this->prefs_transporteur->set( $port );
						}
						// enregistre les paramètres en base.
						delete_option( KINTPV_PREF_TRANSPORTEURS );
						add_option( KINTPV_PREF_TRANSPORTEURS, $this->prefs_transporteur->serialize() );
					}
				}
			}

			/**
			 * Methode import_paiements
			 *
			 * Importe les préférences liées au moyens de paiement
			 *
			 * @param object $document : Contenu envoyé par KinTPV.
			 */
			protected function import_paiements( &$document ) {
				$tag_liste = $document->getElementsByTagName( 'LISTE_REGLEMENT' );

				if ( $tag_liste && $tag_liste->length > 0 ) {
					$reglements = $tag_liste->item( 0 )->getElementsByTagName( 'REGLEMENT' );
					if ( $reglements ) {
						for ( $i = 0; $i < $reglements->length; $i++ ) {
							$id  = self::get_node_value( $reglements->item( $i ), 'IdTypeLigneTicket' );
							$nom = self::get_node_value( $reglements->item( $i ), 'LibelleType' );

							// update de la liste des règlements.
							$regl = $this->prefs_reglement->get( $id );
							if ( ! $regl ) {
								$regl = new Kintpv_Reglement();
							}

							$regl->id  = $id;
							$regl->nom = $nom;

							$this->prefs_reglement->set( $regl );
						}
						// enregistre les paramètres en base.
						delete_option( KINTPV_PREF_REGLEMENTS );

						add_option( KINTPV_PREF_REGLEMENTS, $this->prefs_reglement->serialize() );
					}
				}
			}

			/**
			 * Methode import_categories
			 *
			 * Importe les catégories dans WooCommerce
			 *
			 * @param object $document : Contenu envoyé par KinTPV.
			 */
			protected function import_categories( &$document ) {
				$tag_liste = $document->getElementsByTagName( 'LISTE_CATEGORIE' );

				if ( $tag_liste && $tag_liste->length > 0 ) {
					$detail = $tag_liste->item( 0 )->getElementsByTagName( 'CATEGORIE' );
					if ( $detail ) {
						for ( $i = 0; $i < $detail->length; $i++ ) {
							$id_kin_tpv           = self::get_node_value( $detail->item( $i ), 'IdCategorie' );
							$url_simple_categorie = self::get_node_value( $detail->item( $i ), 'URL_Simple' );
							$id_pere_kintpv       = self::get_node_value( $detail->item( $i ), 'IdCategoriePere' );
							$libelle              = self::get_node_value( $detail->item( $i ), 'Nom' );
							$description          = self::get_node_value( $detail->item( $i ), 'Description' );
							$position             = self::get_node_value( $detail->item( $i ), 'Position' );

							$url_simple_categorie = ( '' !== $url_simple_categorie ) ? strtr( $url_simple_categorie, $this->unwanted_chars ) : strtr( $libelle, $this->unwanted_chars );

							$id_wc = 0;

							$cat = $this->get_categorie_by_slug( $url_simple_categorie );

							if ( $cat && isset( $cat->term_id ) ) {
								$id_wc                               = $cat->term_id;
								$this->ids_categories[ $id_kin_tpv ] = $id_wc;
								$this->urls_categories[ $id_wc ]     = $cat->slug;
							}

							$urls_categories[ $id_kin_tpv ] = ( '' !== $url_simple_categorie ) ? $url_simple_categorie : $id_kin_tpv;

							// s'il y a un père, il faut le rechercher.
							$id_pere_wc = 0;
							if ( $id_pere_kintpv > 0 && isset( $urls_categories[ $id_pere_kintpv ] ) ) {
								$categorie_pere = $this->get_categorie_by_slug( $urls_categories[ $id_pere_kintpv ] );
								if ( $categorie_pere && isset( $categorie_pere->term_id ) ) {
									$id_pere_wc = $categorie_pere->term_id;
								}
							}

							// gestion du suffixe pour la gestion des options.
							if ( $id_wc > 0 ) {
								Kintpv_Log::log( 'Catégorie [' . $libelle . '] trouvée avec l\'id ' . $id_wc );

								$crea = false;
							} else {
								Kintpv_Log::log( 'Catégorie [' . $libelle . '] inexistante' );
								$crea = true;
							}

							if ( $this->option_checked( 'categorie', $crea ) ) {
								$datas = array();

								// on insère dans la reference l'id KinTPV pour faire la liaison.
								$datas['slug'] = ( '' !== $url_simple_categorie ) ? $url_simple_categorie : '';
								// le pere.
								if ( $id_pere_wc > 0 ) {
									$datas['parent'] = $id_pere_wc;
								}
								// nom et description.
								if ( $this->option_checked( 'nom_categorie', $crea ) ) {
									$datas['name'] = $libelle;
								}
								if ( $this->option_checked( 'desc_categorie', $crea ) ) {
									$datas['description'] = $description;
								}
								// l'ordre d'affichage.
								if ( $this->option_checked( 'pos_categorie', $crea ) ) {
									$datas['menu_order'] = $position;
								}

								$datas['id_kin'] = $id_kin_tpv;

								$maj = 'Oui';

								if ( $id_wc > 0 ) {
									// on ne fait la mise à jour que s'il y a eu changement.
									if ( $cat->parent !== $id_pere_wc
									|| $cat->name !== $libelle
									|| $cat->description !== $description ) {
										$this->update_categorie( $id_wc, $datas );
									} else {
										$maj = 'Non';
									}
								} else {
									$this->create_categorie( $datas );
								}

								echo '<category_ok>' . esc_attr( $id_kin_tpv ) . '</category_ok>';
								echo '<maj>' . esc_attr( $maj ) . '</maj>';
								$this->flush();
								$this->check_timer_send();
							}
						}
					}
				}
			}

			/**
			 * Methode import_products
			 *
			 * Importe les produits dans WooCommerce
			 *
			 * @param object $document : Contenu envoyé par KinTPV.
			 */
			protected function import_products( &$document ) {
				set_time_limit( 0 );
				$tag_liste = $document->getElementsByTagName( 'LISTE_ARTICLE' );

				if ( $tag_liste && $tag_liste->length > 0 ) {
					$products = $tag_liste->item( 0 )->getElementsByTagName( 'ARTICLE' );
					if ( $products ) {
						$tab_retour['received'] = $products->length;
						// Boucle sur les produits.
						for ( $i = 0; $i < $products->length; $i++ ) {
							$article_ok = false;

							// première chose on extrait le code barre.
							$sku = self::get_node_value( $products->item( $i ), 'ArticleCodeBarre' );
							Kintpv_Log::log( '--- Import product sku : ' . $sku . '---' );

							// Doit-on le publier ou le dépublier ?
							$is_publier_web = (bool) self::get_node_value( $products->item( $i ), 'PublierWeb_O_N' );

							// On recherche le produit ou la variation correspondant au codebarre reç.
							$art = $this->get_product( $sku );

							$nb_articles_found = count( $art ); // On stock le nombre de résultats dans art, pour définir par la suite si on importe ou pas.
						

							$variation = ( ! empty( $art ) && 'product_variation' === $art[0]->post_type ) ? $art[0] : false;

							// Si l'article récupéré est vide, on est en création ( TRUE ), sinon on est en modification ( FALSE ) ).
							$crea = ( empty( $art ) ) ? true : false;

							// Récupération des infos du produit depuis le fichier XML.
							$id_kin_tpv          = 0;
							$product             = $this->extract_product_info( $products->item( $i ), $crea, $id_kin_tpv );

							$product['in_stock'] = true;


							// Récupération d'éventuelles déclis du produit dans l'XML.
							$infos_variations = $this->extract_variations( $products->item( $i ), $crea );
							
							if ( ! $art && count( $infos_variations['variations'] ) ) {
								$var_as_arts   = array();
								$var_art_infos = array();

								foreach ( $infos_variations['variations'] as $var_art ) {
									$prod = $this->get_product( $var_art['sku'] );
									if ( ! empty( $prod ) && 'product' === $prod[0]->post_type ) {
										$crea            = false;
										$var_as_arts[]   = $prod[0];
										$var_art_infos[] = $var_art;
									}
								}
							}
							
							$this->import_attributes( $infos_variations['attributes'] );
							// boucle sur les attributs pour les affecter au produit.

							$cpt = 1;
							
							foreach ( $infos_variations['attributes'] as $key => $options ) {
								$attr = $this->get_attribute( $key );

								if ( isset( $attr->id ) ) {
									$a['id']        = $attr->id;
									$a['name']      = $this->slugify($key, 'pa_');
									$a['position']  = $cpt;
									$a['visible']   = 1;
									$a['variation'] = 1;
									$a['options']   = $options;

									$product['attributes'][] = (object)$a;

									$cpt++;
								} else {
									continue;
								}
							}

							foreach ( $infos_variations['variations'] as $i_key => $info_var ) {
								foreach ( $info_var['attributes'] as $v_key => $variation_attribute ) {
									$get_attr = $this->get_attribute( $variation_attribute['name'] );
									if ( isset( $get_attr->id ) ) {
										$infos_variations['variations'][ $i_key ]['attributes'][ $v_key ]['id'] = $get_attr->id;
									}
								}
							}

							// gestion des prix promos.
							if ( $infos_variations['promo'] && $this->option_checked( 'promos', $crea ) ) {
								$product['sale_price'] = $infos_variations['promo']['prix'];

								if ( $infos_variations['promo']['debut'] ) {
									$product['date_on_sale_from'] = $infos_variations['promo']['debut'];
								}
								if ( '' !== $infos_variations['promo']['fin'] && '0000-00-00T00:00:00' !== $infos_variations['promo']['fin'] ) {
									$product['date_on_sale_to'] = $infos_variations['promo']['fin'];
								} else {
									$product['date_on_sale_to'] = '';
								}
							} elseif ( ! $infos_variations['promo'] && $this->option_checked( 'promos', $crea ) ) {
								$product['date_on_sale_from'] = '';
								$product['date_on_sale_to']   = '';
								$product['sale_price']        = '';
							}

							// ajout des critères ( qui sont egalement des attributs ).
							for ( $c = 1; $c <= 12; $c++ ) {
								if ( '' !== $this->tab_criteres[ $c ] && $this->option_checked( 'crit' . $c, $crea ) ) {
									$crit = self::get_node_value( $products->item( $i ), 'CRITERE_' . $c );

									if ( '' !== $crit ) {
										$attr_crit = array(
											$this->tab_criteres[ $c ] => array(
												$crit,
											),
										);

										$this->import_attributes( $attr_crit, true );
									}

									$attr = $this->get_attribute( $this->tab_criteres[ $c ], true );
									if ( '' !== $crit && isset($attr->id) ) {
										
										$a['id']                 = $attr->id;
										$a['name']               = $this->slugify($this->tab_criteres[ $c ], 'pa_', '_crit');
										$a['position']           = $c;
										$a['visible']            = 1;
										$a['variation']          = 0;
										$a['options']            = array( $crit );
										$product['attributes'][] = (object)$a;
									}
								}
							}

							$id_article = 0;

							// s'il n'y en a qu'un c'est un article existant donc mise à jour.
							if ( false === (bool) $crea ) {
								Kintpv_Log::log( 'Existing product. Updating' );
								$tags_temp = array();

								if ( $this->option_checked( 'autoriser', $crea ) ) {
									if ( $art && ! $variation ) {
										$id_article    = $art[0]->ID;
										$product['id'] = $art[0]->ID;

										$tags_art = $this->get_product_tags($id_article);

										foreach ( $tags_art as $t ) {
											$tags_temp[] = $t->id;
										}

										if ( $this->option_checked( 'categorie', $crea ) ) {
											$product['categories'] = array();

											$liste_art = $document->getElementsByTagName( 'LISTE_ARTICLE_CATEGORIE' );

											$cats = $this->extract_product_categories( $liste_art, $product['meta_data'][0]['value'] );

											if ( $cats ) {
												foreach ($cats as $cat) {
													$product['categories'][] = (int)$cat['id'];
												}
											}
										}

										// Si l'option d'import des meta_keyword dans les étiquettes est cochée pour la modification.
										if ( $this->option_checked( 'metakeyword_tags', $crea ) ) {
											// On récupère les metakeyword dans le fichier XML.

											$tags = $this->extract_product_tags( self::get_node_value( $products->item( $i ), 'MetaKeyword' ) );

											// Que l'on ajoute au tableau tagsTemp contenant déjà les tags du produit existant.
											foreach ( $tags as $t ) {
												$tags_temp[] = $t['id'];
											}
										}

										$product['tags'] = $tags_temp;

										if ( $infos_variations['stocks'] && $this->option_checked( 'stock', $crea ) ) {
											$product['manage_stock']   = true;
											$product['stock_quantity'] = $infos_variations['stocks'][0];
										}

										if ( count( $infos_variations['variations'] ) === 0 ) {
											$product['type']       = 'simple';
											$product['variations'] = null;
										} else {
											$product['type']         = 'variable';
											$product['manage_stock'] = false;
										}

										// c'est un article : traitement normal.
										$this->update_product( $art[0]->ID, $product );

										$article_ok = true;
									} elseif ( $art && $variation ) { // c'est une decli... alors on ne renseigne que les infos de declis.

										if ( $this->option_checked( 'decli', $crea ) > 0 ) {
											$decli = $this->product_to_variation( $product );

											if ( $infos_variations['stocks'] && $this->option_checked( 'stock', $crea ) ) {
												$decli['stock_quantity'] = $infos_variations['stocks'][0];
											}

											$this->update_variation( 0, $variation->ID, $decli, $is_publier_web );

											$article_ok = true;
										}
									} elseif ( ! $art && ! empty( $var_as_arts ) ) { // Des déclis ont été trouvées en tant que produit, on les met à jour.
										Kintpv_Log::log( 'Les déclinaisons du produit ont été trouvées en tant que produits. Mise à jour des produits' );

										foreach ( $var_as_arts as $k => $v_art ) {
											$id_article     = $v_art->ID;
											$product['id']  = $v_art->ID;
											$product['sku'] = $v_art->meta_value;

											$tags_art = $this->wc_get( 'products/tags', array( 'product' => $id_article ) );

											foreach ( $tags_art as $t ) {
												$tags_temp[] = $t;
											}

											$product['type']       = 'simple';
											$product['variations'] = null;

											if ( $this->option_checked( 'stock', $crea ) ) {
												$product['manage_stock']   = true;
												$product['stock_quantity'] = $var_art_infos[ $k ]['stock_quantity'];
											}

											if ( $this->option_checked( 'prix', $crea ) ) {
												$product['regular_price'] = $var_art_infos[ $k ]['regular_price'];
											} else {
												unset( $product['regular_price'] );
											}

											if ( $var_art_infos[ $k ]['sale_price'] && $this->option_checked( 'promos', $crea ) ) {

												if ( $var_art_infos[ $k ]['date_on_sale_from'] ) {
													$product['date_on_sale_from'] = $var_art_infos[ $k ]['date_on_sale_from'];
												}
												if ( '' !== $var_art_infos[ $k ]['date_on_sale_to'] && '0000-00-00T00:00:00' !== $var_art_infos[ $k ]['date_on_sale_to'] ) {
													$product['date_on_sale_to'] = $var_art_infos[ $k ]['date_on_sale_to'];
												} else {
													$product['date_on_sale_to'] = '';
												}
											} elseif ( ! $var_art_infos[ $k ]['sale_price'] || ! $this->option_checked( 'promos', $crea ) ) {
												$product['date_on_sale_from'] = '';
												$product['date_on_sale_to']   = '';
												$product['sale_price']        = '';
											}

											foreach ( $var_art_infos[ $k ]['attributes'] as $attr ) {
												$found = false;
												foreach ( $product['attributes'] as $k => $prod_attribute ) {
													if ( $prod_attribute['name'] === $attr['name'] ) {
														$product['attributes'][ $k ]['options'] = array(
															$attr['option'],
														);

														$found = true;
														break;
													}
												}
												if ( false === $found ) {
													$product['attributes'][] = array(
														'name' => $attr['name'],
														'options' => array(
															$attr['option'],
														),
														'position' => 1,
														'visible' => 1,
													);
												}
											}

											$product['tags'] = $tags_temp;
											$product['name'] = $v_art->post_title;

											$this->update_product( $v_art->ID, $product );

											$article_ok = true;
										}
									}
								}
								$this->check_timer_send();
							} elseif ( true === (bool) $crea ) {// le produit n'existe pas, on le créé.
								Kintpv_Log::log( 'New product. Creating...' );
								if ( self::get_node_value( $products->item( $i ), 'PublierWeb_O_N' ) === 0 ) {
									Kintpv_Log::log( 'Produit [' . $product['name'] . '] en dépublication inexistant dans WooCommerce : Aucune création' );
									$article_ok = true;
									$this->check_timer_send();
								} else {
									if ( $this->option_checked( 'autoriser', $crea ) ) {
										// les catégories liées.
										if ( $this->option_checked( 'categorie', $crea ) ) {
											$liste_art = $document->getElementsByTagName( 'LISTE_ARTICLE_CATEGORIE' );

											$cats = $this->extract_product_categories( $liste_art, $product['meta_data'][0]['value'] );		

											if ( $cats ) {
												foreach ( $cats as $cat ) {
													$product['categories'][] = (int)$cat['id'];
												}
											}
										}

										if ( $this->option_checked( 'metakeyword_tags', $crea ) ) {
											$tags = $this->extract_product_tags( self::get_node_value( $products->item( $i ), 'MetaKeyword' ) );
											
											
											$tags_temp = array();
											foreach ( $tags as $t ) {
												$tags_temp[] = $t['id'];
											}

											$product['tags'] = $tags_temp;
										}

										if ( $infos_variations['stocks'] && $this->option_checked( 'stock', $crea ) ) {
											$product['manage_stock']   = true;
											$product['stock_quantity'] = $infos_variations['stocks'][0];
										}

										$art = $this->create_product( $product );

										if ( $art ) {
											$id_article = $art->id;
											$article_ok = true;
										}
									}
								}
							} elseif ( $nb_articles_found > 0 ) {
								// il y a une erreur, plusieurs articles correspondent... il faut loguer et retourner une erreur.
								Kintpv_Log::log( 'ERREUR : Plusieurs articles avec le même code barre : [' . $sku . ']. Référence non importée !' );
							}

							$publi_web = ( self::get_node_value( $products->item( $i ), 'PublierWeb_O_N' ) === 0 ) ? false : true;
							
							if ( empty( $var_as_arts ) && ( true === $publi_web || ( false === $publi_web && false === $crea ) ) ) { // Si les déclis Kintpv ne sont pas des produits simple sur WC , on importe les décli.
								$this->import_variations( $id_article, $infos_variations['variations'], $publi_web );
							}

							if ( $article_ok ) {
								echo '<product_ok>' . esc_attr( $id_kin_tpv ) . '</product_ok>';
								$this->flush();
							}

							$this->check_timer_send();
						}
					}
				}
			}

			/**
			 * Methode get_product_tags
			 * 
			 * Retourne la liste des étiquettes d'un produit
			 * 
			 * @param int $id : Id du produit
			 */
			public static function get_product_tags($id)
			{
				global $wpdb;

				$array_tags = array();
				
				$tags = $wpdb->get_results(
					$wpdb->prepare(
						"Select * from {$wpdb->prefix}term_taxonomy
						Inner join {$wpdb->prefix}terms on {$wpdb->prefix}terms.term_id = {$wpdb->prefix}term_taxonomy.term_id
						Inner join {$wpdb->prefix}term_relationships on {$wpdb->prefix}term_relationships.term_taxonomy_id = {$wpdb->prefix}term_taxonomy.term_id
						Where {$wpdb->prefix}term_relationships.object_id = %d
						And {$wpdb->prefix}term_taxonomy.taxonomy = 'product_tag'",
						$id
					)
				);

				foreach ($tags as $tag) {
					$temp_tag = array();

					$temp_tag['id']         = (int)$tag->term_id;
					$temp_tag['name']       = $tag->name;
					$temp_tag['slug']       = $tag->slug;
					$temp_tag['descrition'] = $tag->description;
					$temp_tag['count']      = (int)$tag->count;

					$array_tags[] = (object)$temp_tag;
				}

				return $array_tags;
			}

			/**
			 * Methode create_product_tag
			 * 
			 * Insère un nouveau term de type 'product_tag dans la base
			 * 
			 * @param array $data : Infos (nom, slug) du nouveau tag.
			 */
			public function create_product_tag( $data )
			{
				global $wpdb;// Variable d'accès à la BDD de wordpress.
				
				$term_id = 0;

				$name = $data['name'];
				$slug = isset($data['slug']) ? $data['slug'] : $this->slugify($data['name']);

				//On recherche si aucun tag n'existe pour ce nom
				$search = $this->search_product_tag($name);

				if ( sizeof( $search ) > 0 ) {
					$result = array();
					$result['message'] = "Une étiquette produit existe déjà pour le nom '{$name}'.";

					return (object)$result;
				}

				//Insertion dans wp_terms avec le nom
				$result = $wpdb->get_results(
					$wpdb->prepare(
						"INSERT INTO {$wpdb->prefix}terms (name, slug, term_group) values (%s, %s, 0)",
						$name,
						$slug
					)
				);
				
				if ($wpdb->insert_id > 0) {
					$term_id = $wpdb->insert_id;
					//Ensuite insertion dans term_taxonomy avec l'id de l'insertion et product_tag dans taxonomy

					$result = $wpdb->get_results(
						$wpdb->prepare(
							"INSERT INTO {$wpdb->prefix}term_taxonomy (term_id, taxonomy) values (%d, 'product_tag')",
							$term_id,
						)
					);

					if ($wpdb->insert_id > 0) {
						return $term_id;
					} else {
						return $wpdb->las_error;
					}
				}
			}

			/**
			 * Methode search_product_tag
			 * 
			 * Recherche une étiquette produit par sa valeur (name)
			 * 
			 * @param string $name : Nom du tag à chercher.Z
			 */
			public function search_product_tag($name)
			{
				global $wpdb; // Variable d'accès à la BDD de wordpress.
				$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * from {$wpdb->prefix}terms
						inner join {$wpdb->prefix}term_taxonomy on {$wpdb->prefix}terms.term_id = {$wpdb->prefix}term_taxonomy.term_id
						where {$wpdb->prefix}terms.name = %s 
						and {$wpdb->prefix}term_taxonomy.taxonomy = 'product_tag'",
						$name
					)
				);

				if ( ! empty( $result )) {
					return $result;
				} else {
					return array();
				}
			}

			/**
			 * Methode get_product_by_variation
			 *
			 * Retourne un produit à partir d'une de ses déclinaisons
			 *
			 * @param int $id_variation : Id de la déclinaison.
			 */
			protected function get_product_by_variation( $id_variation ) {
				global $wpdb;

				if ( 0 !== $id_variation ) {
					$product = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * from {$wpdb->prefix}posts where ID = (SELECT post_parent from {$wpdb->prefix}posts where ID = %d)",
							$id_variation
						)
					);

					if ( ! empty( $product ) ) {
						return $product[0];
					}
				}

				return null;
			}

			/**
			 * Methode extract_product_categories
			 *
			 * Récupère les catégories d'un produit
			 *
			 * @param object $document : Contenu envoyé par KinTPV.
			 * @param int    $id_product : Id du produit concerné.
			 */
			protected function extract_product_categories( $document, $id_product ) {
				global $wpdb;
				$cats = array();
				foreach ( $document[0]->childNodes as $key ) {
					if ( 'ARTICLE_CATEGORIE' === $key->nodeName ) {
						if ( self::get_node_value( $key, 'IdArticle' ) === $id_product ) {
							$cats[] = self::get_node_value( $key, 'URL_Simple' );
						}
					}
				}

				$cats_id = array();
				Kintpv_Log::log( 'Article lié à ' . count( $cats ) . ' catégories' );

				if ( $cats && count( $cats ) > 0 ) {
					foreach ( $cats as $c ) {
						$c = $this->slugify( $c );

						$categorie = $this->get_categorie_by_slug( $c );

						$id_categorie = ( null !== $categorie ) ? $categorie->term_id : 0;

						if ( 0 !== $id_categorie ) {
							Kintpv_Log::log( 'Catégorie #' . $categorie->term_id . ' : ' . $categorie->name );

							if ( isset( $categorie ) && isset( $categorie->term_id ) ) {
								// et mettre l'id dans le tableau.
								$cats_id[]['id'] = $categorie->term_id;
							}
						}
						$this->check_timer_send();
					}
				}

				return $cats_id;
			}

			/**
			 * Methode get_categorie_by_slug
			 *
			 * Retourne une catégorie à partir de son slug
			 *
			 * @param string $slug : Slug de la categorie recherchée.
			 */
			protected function get_categorie_by_slug( $slug ) {
				global $wpdb;

				$categorie = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * from {$wpdb->prefix}terms join {$wpdb->prefix}term_taxonomy where {$wpdb->prefix}term_taxonomy.term_id   = {$wpdb->prefix}terms.term_id and {$wpdb->prefix}terms.slug = %s",
						$slug
					)
				);

				$return = ( ! empty( $categorie ) ) ? $categorie[0] : null;

				return $return;
			}

			/**
			 * Méthode extract_product_tags
			 *
			 * Méthode de récupération des étiquettes du produit importé par KinYPV
			 *
			 * @param string $document : Chaine de caractères contenu dans la balise <MetaKeyword> du fichier XML reçu.
			 */
			protected function extract_product_tags( $document ) {
				$product_tags = array();

				// On commence par récupérer chaque keyword dans une case d'un tabelau.
				$tags = explode( ',', $document );

				// Puis on boucle sur ce tableau s'il n'est pas vide.
				if ( ! empty( $tags ) && '' !== $tags[0] ) {
					foreach ( $tags as $t ) {
						// On retire le premier espace s'il y en a un.
						$t = preg_replace( '/\s/u', '', $t, 1 );

						// Puis on cherche par l'API si un tag avec ce nom existe.
						$search_tags = $this->search_product_tag( $t );

						// Si on obtient un ou plusieurs résultats, on boucle dessus.
						if ( count( $search_tags ) > 0 ) {
							foreach ( $search_tags as $s_tag ) {
								// Les résultats pouvant seulement CONTENIR la chaîne de notre keyword.
								// On s'assure que ce soit exactement le bon nom, pour ne pas attribuer une étiquette non désirée.
								// au produit.
								if ( $s_tag->name === $t ) {
									// Si ça corresponds, on insère son id dans le tableau.
									$product_tags[]['id'] = (int)$s_tag->term_id;
									break;
								} else {
									// Sinon on en crée un nouveau tag.
									// Puis on insère son id dans le tableau.
									$data = array(
										'name' => $t,
									);

									//Insertion du tag
									$new_tag = $this->create_product_tag($data);

									if ( isset( $new_tag->message ) ) {
										Kintpv_Log::log( 'Erreur WooCommerce : ' . $new_tag->message );
										return;
									}

									$product_tags[]['id'] = $new_tag;
									break;
								}
							}
						} else {
							// Sinon on en crée un nouveau tag.
							// Puis on insère son id dans le tableau.
							$data = array(
								'name' => $t,
							);

							$new_tag = $this->create_product_tag($data);

							if ( isset( $new_tag->message ) ) {
								Kintpv_Log::log( 'Erreur WooCommerce : ' . $new_tag->message );
								return;
							}

							$product_tags[]['id'] = $new_tag;
						}

						$this->check_timer_send();
					}
				}

				return $product_tags;
			}

			/**
			 * Methode extract_variations
			 *
			 * Récupère les déclinaisons d'un produit depuis le fichier XML
			 *
			 * @param object $document : Contenu envoyé par KinTPV.
			 * @param bool   $crea : Est-on en creation ou modification d'un produit/decli.
			 */
			protected function extract_variations( $document, $crea ) {
				$attributs     = array();
				$variations    = array();
				$stock_product = array();
				$promo         = array();
				$tag           = $document->getElementsByTagName( 'LISTE_STOCK' );

				if ( $tag && $tag->length > 0 ) {
					$tag_stocks = $tag->item( 0 )->getElementsByTagName( 'STOCK' );
					if ( $tag_stocks ) {
						// boucle sur les fiches stock.
						for ( $i = 0; $i < $tag_stocks->length; $i++ ) {
							// lecture du prix promo ( s'il y a ).
							$tag_pspp = $tag_stocks->item( $i )->getElementsByTagName( 'PSPP' );
							$promo    = array();

							if ( $tag_pspp && $tag_pspp->length > 0 ) {
								$promo['prix']  = self::format_float( self::get_node_value( $tag_pspp->item( 0 ), 'PSPP_PrixTTC' ) );
								$promo['solde'] = (int) self::get_node_value( $tag_pspp->item( 0 ), 'PSPP_Solde_O_N' );
								$promo['promo'] = (int) self::get_node_value( $tag_pspp->item( 0 ), 'PSPP_Promo_O_N' );
								$promo['debut'] = self::get_node_value( $tag_pspp->item( 0 ), 'PSPP_Debut' );
								$promo['fin']   = self::get_node_value( $tag_pspp->item( 0 ), 'PSPP_Fin' );
							}

							// recherche de la declinaison.
							$tag_attributes = $tag_stocks->item( $i )->getElementsByTagName( 'DECLINAISON' );
							if ( $tag_attributes && $tag_attributes->length > 0 ) {
								$tab_attr    = array();
								$sku         = self::get_node_value( $tag_stocks->item( $i ), 'CodeBarre' );
								$scale_price = self::format_float( self::get_node_value( $tag_stocks->item( $i ), 'PV_DiffTTC' ) );
								$qte         = (int) self::get_node_value( $tag_stocks->item( $i ), 'Quantite' );

								// boucle sur les fiches declinaison.
								for ( $j = 0; $j < $tag_attributes->length; $j++ ) {
									$attr = $this->extract_attribute_product( $tag_attributes->item( $j ) );
									if ( '' !== $attr['name'] ) {
										if ( ! isset( $attributs[ $attr['name'] ] ) ) {
											$attributs[ $attr['name'] ] = array();
										}

										if ( ! in_array( $attr['value'], $attributs[ $attr['name'] ] ) ) {
											$attributs[ $attr['name'] ][] = $attr['value'];
										}

										$tab_attr[] = array(
											'name'   => $attr['name'],
											'option' => $attr['value'],
										);
									}
								}

								$infos_variation = array();
								if ( $scale_price ) {
									$infos_variation['regular_price'] = sprintf( '%.02f', self::format_float( self::get_node_value( $document, 'PV_BaseTTC' ) ) + $scale_price );
								} else {
									$infos_variation['regular_price'] = self::format_float( self::get_node_value( $document, 'PV_BaseTTC' ) );
								}
								$infos_variation['sku']            = $sku;
								$infos_variation['manage_stock']   = true;
								$infos_variation['stock_quantity'] = $qte;
								$infos_variation['attributes']     = $tab_attr;
								$infos_variation['meta_data']      = array();
								$infos_variation['weight'] = self::get_node_value($tag_stocks->item( $i ), 'Poids');
								
								$infos_variation['meta_data'][]    = array(
									'key'   => KINTPV_META_ID_ARTICLE,
									'value' => self::get_node_value( $document, 'IdArticle' ),
								);

								if ( $promo && $this->option_checked( 'promos', $crea ) ) {
									$infos_variation['sale_price'] = $promo['prix'];
									if ( '' !== $promo['debut'] && '0000-00-00T00:00:00' !== $promo['debut'] ) {
										$infos_variation['date_on_sale_from'] = $promo['debut'];
									}
									if ( '' !== $promo['fin'] && '0000-00-00T00:00:00' !== $promo['fin'] ) {
										$infos_variation['date_on_sale_to'] = $promo['fin'];
									} else {
										$infos_variation['date_on_sale_to'] = '';
									}
								} elseif ( ! $promo && $this->option_checked( 'promos', $crea ) ) {
									$infos_variation['date_on_sale_from'] = '';
									$infos_variation['date_on_sale_to']   = '';
									$infos_variation['sale_price']        = '';
								}

								$promo = array();
								// ceci forme une declinaison.
								$variations[] = $infos_variation;
							} else {
								// c'est un article sans declinaison, on recupère uniquement le stock.
								$stock_product[] = (int) self::get_node_value( $tag_stocks->item( $i ), 'Quantite' );
							}

							$this->check_timer_send();
						}
					}
				}

				return array(
					'attributes' => $attributs,
					'variations' => $variations,
					'stocks'     => $stock_product,
					'promo'      => $promo,
				);
			}


			/**
			 * Methode mettre_article_en_decline
			 *
			 * Transforme un article simple en article avec déclinaisons
			 *
			 * @param int $id_product : Id du produit à transformer.
			 */
			protected function mettre_article_en_decline( $id_product ) {
				$product = new WC_Product_Variable($id_product);
				$product->save();
			}
			

			/**
			 * Methode import_variations
			 *
			 * Improt des déclis d'un produit
			 *
			 * @param int   $id_product : Id du produit dans lequel importer les déclis.
			 * @param array $variations : Tableau déclis à importer.
			 * @param bool  $is_publier_web : Rendre les déclis actives oui/non.
			 */
			protected function import_variations( $id_product, $variations, $is_publier_web ) {
				$product_decline = false;
				foreach ( $variations as $v ) {
					$declinaison_ok = false;
					$id_decli       = 0;
					$declinaison    = $this->get_product_variation( $id_product, $v['sku'] );
					

					if ( is_array( $declinaison ) ) {
						if ( $this->option_checked( 'decli', false ) ) {
							if ( ! $this->option_checked( 'stock', false ) ) {
								unset( $v['stock_quantity'] );
							}
							if ( ! $this->option_checked( 'prix_decli', false ) ) {
								unset( $v['regular_price'] );
							}
							if ( ! $this->option_checked( 'poids_decli', false ) ) {
								unset( $v['weight'] );
							}
							if ( ! $this->option_checked( 'promos', false ) ) {
								unset( $v['sale_price'] );
								unset( $v['date_on_sale_from'] );
								unset( $v['date_on_sale_to'] );
							}

							if ( false === $product_decline ) {
								$this->mettre_article_en_decline( $id_product );
								$product_decline = true;
							}

							$this->update_variation( $id_product, $declinaison['id'], $v, $is_publier_web );
							$id_decli = $declinaison['id'];

							$declinaison_ok = true;
						} else {
							echo '<declinaisons_nok>' . esc_attr( $declinaison['id'] ) . '</declinaisons_nok>';
						}
					} else {
						
						Kintpv_Log::log( 'Declinaison not found. Trying to create' );
						$art = $this->get_product( $v['sku'] );

						// il faut rechercher si ce n'est pas un article.
						if ( isset( $art[0]->ID ) ) {
							// mise à jour des informations de stock et de prix de l'article.
							if ( $this->option_checked( 'stock', false ) ) {
								$art['in_stock']       = true;
								
								if ($this->m_is_service_hs === false) {
									$art['stock_quantity'] = $v['stock_quantity'];
								}

								if ( $this->option_checked( 'promos', false ) ) {
									$art['sale_price']        = $v['sale_price'];
									$art['date_on_sale_from'] = $v['date_on_sale_from'];

									if ( '' !== $v['date_on_sale_to'] && '0000-00-00T00:00:00' !== $v['date_on_sale_to'] ) {
										$art['date_on_sale_to'] = $v['date_on_sale_to'];
									} else {
										$art['date_on_sale_to'] = '';
									}
								}
							}

							$this->update_product( $art[0]->ID, $art );

							$id_decli       = $art[0]->ID;
							$declinaison_ok = true;
						} else {
							// creation de la declinaison.
							if ( $this->option_checked( 'decli', true ) ) {
								if ( ! $this->option_checked( 'stock', true ) ) {
									unset( $v['stock_quantity'] );
								}
								if ( ! $this->option_checked( 'prix_decli', true ) ) {
									unset( $v['regular_price'] );
								}
								if ( ! $this->option_checked( 'poids_decli', true ) ) {
									unset( $v['weight'] );
								}
								if ( ! $this->option_checked( 'promos', true ) ) {
									unset( $v['sale_price'] );
									unset( $v['date_on_sale_from'] );
									unset( $v['date_on_sale_to'] );
								}

								if ( false === $product_decline ) {
									$this->mettre_article_en_decline( $id_product );
									$product_decline = true;
								}

								$decli = $this->create_variation( $id_product, $v );

								if ( isset( $decli->id ) ) {
									$id_decli       = $decli->id;
									$declinaison_ok = true;
								}
							} else {
								echo '<declinaisons_nok>-1</declinaisons_nok>';
							}
						}
					}

					$this->check_timer_send();

					if ( $declinaison_ok ) {
						echo '<declinaisons_ok>' . esc_attr( $id_decli ) . '</declinaisons_ok>';
					}
				}

				// Ensuite il faut supprimer les déclis ayant été retirés du produit.
				// On récupère d'abord le nombre de déclis appartenant à l'article.
				$product_variations = $this->wc_get( 'products/' . $id_product . '/variations' );

				// Si on a + de déclis sur le produit de WooCommerce que le produit envoyé par KinTPV.
				if ( ( is_array( $product_variations ) || $product_variations instanceof Countable )
				&& ( is_array( $variations ) || $variations instanceof Countable )
				&& ( count( $product_variations ) > count( $variations ) ) ) {
					foreach ( $variations as $v ) {
						// Alors on boucle sur les déclis envoyées, puis sur chaque décli de WC.
						foreach ( $product_variations as $key => $p_v ) {
							// Si la décli de WooCommerce est présente dans les déclinaisons envoyées par KinTPV ( Recherche par le SKU ).
							// Alors on conserve cette déclinaison, on retire l'entrée du tableau productVariations.
							if ( $v['sku'] === $p_v->sku ) {
								unset( $product_variations[ $key ] );
							}

							$this->check_timer_send();
						}
					}

					// Une fois les déclinaisons à conserver retirées de ce tableau, il ne nous reste que les déclis "en trop" du produit dans WooCommerce.
					// Donc on les supprime.
					foreach ( $product_variations as $p_v ) {
						$this->delete_variation( $id_product, $p_v->id );
						$this->check_timer_send();
					}
				}
			}

			/**
			 * Methode extract_attribute_product
			 *
			 * Récupération dans le fichier XML d'un attribut du produit
			 *
			 * @param string $tag : Balise XML de l'attribut.
			 */
			protected function extract_attribute_product( $tag ) {
				$attribute['name']     = self::get_node_value( $tag, 'NOM' );
				$attribute['value']    = self::get_node_value( $tag, 'DETAIL' );
				$attribute['order_by'] = self::get_node_value( $tag, 'TRI' );
				return $attribute;
			}

			/**
			 * METHODE get_all_attributes
			 *
			 * Récupère tous les attributs présents dans WooCommerce
			 */
			protected function get_all_attributes() {
				global $wpdb;

				$res = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT attribute_id as id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies"
					)
				);

				$array_wc_attributes = array();

				foreach ($res as $attr) {
					$array_wc_attributes[] = wc_get_attribute((int)$attr->id);
				}

				return $array_wc_attributes; 
			}

			/**
			 * Methode get_all_attribute_terms
			 *
			 * Récupération des termes d'un attribut
			 *
			 * @param int $id : Id de l'attribut.
			 */
			protected function get_all_attribute_terms( $id ) {
				$taxonomy = wc_get_attribute($id);
				
				return get_terms($taxonomy->slug);
			}

			/**
			 * Methode get_attribute
			 *
			 * Récupère un attribute dans WooCommerce
			 *
			 * @param string $name : Nom de l'attribut à rechercher.
			 * @param bool   $is_critere (default = false) : Si c'est un critère on ajoute un suffixe au slug de recherche.
			 */
			protected function get_attribute( $name, $is_critere = false ) {
				global $wpdb; // Initialise la connexion à la DB de WordPress.

				$attr = null;
				$slug = ( true === $is_critere ) ? $this->slugify( $name, '', '_crit' ) : $this->slugify( $name );

				$res = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT attribute_id as id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
						$slug
					)
				);

				if ( ! empty( $res ) ) {
					$attr = (object)$res[0];
				}

				$this->check_timer_send();
				
				return $attr;
			}

			/**
			 * Methode get_attribute_term
			 *
			 * Récupération d'un terme d'un attribut
			 *
			 * @param int    $id : Id de l'attribut.
			 * @param string $opt : Valeur du terme à rechercher.
			 */
			protected function get_attribute_term( $id, $opt ) {
				$attribute_terms = $this->get_all_attribute_terms( $id );

				$term = null;

				foreach ( $attribute_terms as $t ) {
					if ( $t->name === $opt && $t->slug === $this->slugify( $opt ) ) {
						$term = $t;
						break;
					}
				}

				return $term;
			}

			/**
			 * Methode import_attributes
			 *
			 * Import des attributs dans WooCommerce
			 *
			 * @param array $product_attributes : Attributs à importer.
			 * @param bool  $is_critere : On précise si c'est un critère KinTPV ou non.
			 */
			protected function import_attributes( $product_attributes, $is_critere = false ) {
				// Si pb valeurs manquantes : enlever la variable membre this->wc_attributes.
				$this->wc_attributes = $this->get_all_attributes();

				$data = array();
				foreach ( $product_attributes as $key => $values ) {					
					$slugged_attribute = ( true === (bool)$is_critere ) ? $this->slugify( $key, 'pa_', '_crit' ): $this->slugify( $key, 'pa_' );

					$attr_exists = false;
					$attr_id     = 0;

					foreach ( $this->wc_attributes as $wc_attr ) {
						if ( $wc_attr->slug == $slugged_attribute ) {
							$attr_exists = true;
							$attr_id     = $wc_attr->id;
							break;
						}
					}
					
					if ( $attr_exists ) {
						$this->import_attributes_terms( $values, $attr_id );
					} else {
						$data = array(
							'name'     => $key,
							'slug'     => $slugged_attribute,
							'order_by' => 'menu_order',
						);
						
						$new_attribute = wc_create_attribute( $data );
						if ( isset( $new_attribute->errors ) ) {
							Kintpv_Log::log( "Appel de l'URL échoué ( POST ) : " . 'products/attributes' );
							Kintpv_Log::log( 'Erreurs WooCommerce : ');
						} elseif ( 0 !== (int)$new_attribute ) {
							$this->import_attributes_terms( $values, $new_attribute );
						}
					}

					$this->check_timer_send();
				}
			}

			/**
			 * Methode import_attributes_terms
			 *
			 * Importe les membres des attributs dans WooCommerce
			 *
			 * @param int   $id (default = 0) : Id de l'attribut auquel importer les termes.
			 * @param array $terms : Termes à importer.
			 */
			protected function import_attributes_terms( $terms, $id = 0 ) {
				$attribute_terms = $this->get_all_attribute_terms( $id );

				foreach ( $terms as $k => $t ) {
					$slugged_term = $this->slugify( $t );

					$term_exists = false;
					if ( ! empty( $attribute_terms ) ) {
						foreach ( $attribute_terms as $key => $attr_term ) {
							if ( $attr_term->slug === $slugged_term ) {
								$term_exists = true;
								break;
							}
						}
					}

					if ( ! $term_exists && isset( $id ) ) {
						$data = array(
							'name' => "allo",
							'slug' => $slugged_term,
						);

						$taxonomy = wc_get_attribute($id);
						$term = wp_insert_term($t, $taxonomy->slug);

						if ( isset( $term->code ) ) {
							Kintpv_Log::log( "Appel de l'URL échoué ( POST ) : " . 'products/attributes/' . $id . '/terms' );

							Kintpv_Log::log( 'Erreur WooCommerce : ' . $term->message );
						}
					}

					$this->check_timer_send();
				}
			}

			/**
			 * Methode slugify
			 *
			 * Retourne une chaine de caractères au format slug pour WooCommerce
			 *
			 * @param string $value : Chaine à transformer.
			 * @param string $prefix (default = '') : Préfix à ajouter au début du slug.
			 * @param string $suffix (default = '') : Suffixe à ajouter à la fin du slug.
			 */
			protected function slugify( $value, $prefix = '', $suffix = '' ) {
				// remplacer les caratères non-chiffre ou lettre par un '-' .
				$value = preg_replace( '~[^\pL\d]+~u', '-', $value );

				// On s'assure de l'encodage de la chaine.
				$value = iconv( 'utf-8', 'us-ascii//TRANSLIT', $value );

				// Retrait des caractères indésirables restants.
				$value = preg_replace( '~[^-\w]+~', '', $value );

				// Retrait des - dupliqués.
				$value = preg_replace( '~-+~', '-', $value );

				// Ajout des préfix et suffixe en forçant les minuscules.
				$value = strtolower( $prefix ) . strtolower( $value ) . strtolower( $suffix );

				// trim.
				$value = trim( $value, '-' );

				// Si la longeur de la chaine dépasse 28 caractères , on retourne une erreur à KinTPV et on stoppe l'import.
				if ( strlen( $value ) > 28 ) {
					$this->error = KINTPV_WC_SLUG_LENGTH;
					$this->termine( false, "Le nom '" . $value . "' est trop long. Il ne doit pas dépasser 28 caractères, espaces compris." );
				}

				return $value;
			}

			/**
			 * Methode wc_get
			 *
			 * Methode d'appel de l'API de WooCommerce
			 *
			 * @param string $param : URL à appeler.
			 * @param array  $data (default = null) : Options de l'URL appelé.
			 * @param bool   $termine_on_error (default = true) : Retourner une erreur et arrêter le processus (oui/non) en cas d'erreur.
			 */
			protected function wc_get( $param, $data = null, $termine_on_error = true ) {
				$retour      = null;
				$woocommerce = $this->get_rest_client( $termine_on_error );

				if ( $woocommerce ) {
					if ( $data ) {
						$retour = $woocommerce->get( $param, $data );
					} else {
						$retour = $woocommerce->get( $param );
					}

					if ( isset( $retour->code ) && $termine_on_error ) {
						Kintpv_Log::log( "Appel de l'URL échoué ( GET ): " . $param );

						Kintpv_Log::log( 'Erreur WooCommerce : ' . $retour->message );

						$this->error = KINTPV_WC_ERROR;

						$this->termine( true, $retour->message );
					}
				}

				return $retour;
			}

			/**
			 * Methode get_product
			 *
			 * Récupération d'un produit par son code-barres ou id
			 *
			 * @param string $sku : code-barres du produit recherché.
			 * @param int    $id (default = 0) : Id du produit à récupérer si on le connait.
			 */
			protected function get_product( $sku, $id = 0 ) {
				// Recherche par Id OU codebarre si id = 0.
				global $wpdb;

				$retour = false;
				if ( $id > 0 ) { 
					$retour = wc_get_product($id);
				} else {
					$retour = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * from {$wpdb->prefix}postmeta 
							inner join {$wpdb->prefix}posts where {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id
							and {$wpdb->prefix}postmeta.meta_key = '_sku'
							and {$wpdb->prefix}postmeta.meta_value = %s
							and {$wpdb->prefix}posts.post_status != 'trash'",
							$sku
						)
					);
				}

				return $retour;
			}

			/**
			 * Methode create_product
			 *
			 * Création d'un nouveau produit sur WooCommerce
			 *
			 * @param array $data : Données du nouveau produit.
			 */
			protected function create_product( $data ) {
				if (isset($data['name'])) {
					if (isset($data['slug'])) {
						Kintpv_Log::log( 'Création du produit : [' . $data['name'] . '] ref : [' . $data['slug'] . ']' );
					} else {
						Kintpv_Log::log( 'Création du produit : [' . $data['name'] . ']' );
					}
				}

				$product = new WC_Product();

				$this->set_product_data($product, $data);

				$product->save();
				$ret = $product;

				if ( isset( $ret->code ) ) {
					Kintpv_Log::log( "Appel de l'URL échoué ( POST ) : " . 'products' );

					Kintpv_Log::log( 'Erreur WooCommerce : ' . $ret->message );
				}

				// mise à jour du permalink.
				if ( $ret && isset( $data ['permalink'] ) ) {
					$my_post = array(
						'ID'        => $ret->id,
						'post_name' => $data['permalink'],
					);

					$post_id = wp_update_post( $my_post );
				}

				return $ret;
			}

			/**
			 * Methode update_product
			 *
			 * Mise à jour d'un produit
			 *
			 * @param int   $id : Id du produit.
			 * @param array $data : Infos du produit.
			 */
			protected function update_product( $id, $data ) {
				if ( isset( $data['name'] ) && isset( $data['slug'] ) && isset( $data['sku'] ) ) {
					Kintpv_Log::log( 'Mise à jour du produit : [' . $data['name'] . '] ref : [' . $data['slug'] . '] code barre : [' . $data['sku'] . '] id WC : [' . $id . ']' );
				}

				$product_type = WC_Product_Factory::get_product_type($id);
				
				if ($product_type === 'simple') {
					$product = new WC_Product($id);
				} else {
					$product = new WC_Product_Variable($id);
				}

				$this->set_product_data($product, $data);
				
				$product->save();

				$ret = $product;
				
				if ( isset( $ret->code ) ) {
					Kintpv_Log::log( "Appel de l'URL échoué ( PUT ): " . 'products/' . $id );

					Kintpv_Log::log( 'Erreur WooCommerce : ' . $ret->message );
				}

				// mise à jour du permalink.
				if ( isset( $data ['permalink'] ) ) {
					$my_post = array(
						'ID'        => $id,
						'post_name' => $data['permalink'],
					);

					$post_id = wp_update_post( $my_post );
				}

				return $ret;
			}

			public function set_product_data(&$product, $data)
			{
				foreach ($data as $key => $value)
				{
					switch ($key) {
						case 'name':
							$product->set_name($value);
							break;

						case 'slug':
							$product->set_slug($value);
							break;

						case 'catalog_visibility':
							$product->set_catalog_visibility($value);
							break;

						case 'description':
							$product->set_description($value);
							break;

						case 'short_description':
							$product->set_short_description($value);
							break;

						case 'sku':
							$product->set_sku($value);
							break;

						case 'regular_price':
							$product->set_regular_price($value);
							break;

						case 'virtual':
							$product->set_virtual($value);
							break;

						case 'weight':
							$product->set_weight($value);
							break;

						case 'backorders':
							$product->set_backorders($value);
							break;

						case 'in_stock':
							if ( $this->m_is_service_hs === false ) {
								$val = (true === $value) ? 'instock' : 'outofstock';
								$product->set_stock_status($val);
							} else {
								$product->set_stock_status('instock');
							}
							break;

						case 'date_on_sale_from':
							$product->set_date_on_sale_from($value);
							break;

						case 'date_on_sale_to':
							$product->set_date_on_sale_to($value);
							break;

						case 'sale_price':
							$product->set_sale_price($value);
							break;

						case 'categories':
							$product->set_category_ids($value);
							break;

						case 'tags':
							$product->set_tag_ids($value);
							break;

						case 'stock_quantity':
							if ( $this->m_is_service_hs === false ) {
								$product->set_manage_stock(true);
								$product->set_stock_quantity($value);
							} else {
								$product->set_manage_stock(false);
								$product->set_stock_quantity(0);
							}
							break;

						case 'status':
							$product->set_status($value);
							break;
						
						case 'attributes':
							$array_attributes = array();
							
							foreach ($value as $attr) {
								$wc_attribute = new WC_Product_Attribute(1);
								$wc_attribute->set_id( 1 );
								$wc_attribute->set_name( $attr->name );
								$wc_attribute->set_options( $attr->options );
								$wc_attribute->set_position( $attr->position );
								$wc_attribute->set_visible( $attr->visible );
								$wc_attribute->set_variation( $attr->variation );

								$array_attributes[] = $wc_attribute;
							}

							$product->set_attributes($array_attributes);
							break;

						case 'images':
							if ($this->num != 1) {
								$array_image_ids = $product->get_gallery_image_ids();
							}

							foreach ($value as $img) {
								if (isset($img['id'])) {
									continue;
								}

								$wordpress_upload_dir = wp_upload_dir();
								$new_file_path = $wordpress_upload_dir['path'];

								$insert_id = $this->check_image_exists(WP_PLUGIN_URL.'/kintpv-connect/tmp/'.$img['name']);

								if ($insert_id == -1) {
									$insert_id = $this->insert_image_as_attachment(
										WP_PLUGIN_DIR.'/kintpv-connect/tmp/'.$img['name'],
										WP_PLUGIN_URL.'/kintpv-connect/tmp/'.$img['name'],
										$new_file_path.'/'.$img['name'],
										preg_replace( '/\.[^.]+$/', '', basename( $img['name'] ) ),
										$wordpress_upload_dir['url'] . '/' . basename( $img['name'] )
									);
								}

								if ($this->num == 1) {
									$product->set_image_id($insert_id);
								} else {
									$array_image_ids[] = $insert_id;
								}
							}

							$product->set_gallery_image_ids($array_image_ids);
							$product->save();
							break;
					}
				}
			}

			public function insert_image_as_attachment($src, $src_url, $dest, $title, $guid)
			{
				if (copy($src, $dest)) {
					$insert_id = wp_insert_attachment(
						array( 
							'post_title'      => $title,
							'post_mime_type' => mime_content_type($dest),
							'guid'           => $guid,
							'post_content'   => '',
							'post_status'    => 'inherit'
						),
						$guid
					);

					global $wpdb;

					$wpdb->get_results(
						$wpdb->prepare(
							"INSERT into {$wpdb->prefix}postmeta (post_id, meta_key, meta_value)
							values (%d, '_kintpv_img_md5', %s)",
							$insert_id,
							md5_file($src_url)
						)
					);
					
					unlink($src);
					return $insert_id;
				}
			}

			/**
			 * Vérifie si une image est présente dans le site ou non
			 * 
			 * @param string $url_image : URL de l'image à comparer
			 */
			function check_image_exists($url_image)
			{
				global $wpdb;

				$uploaded_images = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT meta_id, post_id from {$wpdb->prefix}postmeta where meta_key = '_kintpv_img_md5'
						and meta_value = %s",
						md5_file($url_image)
					)
				);

				//Si on a récupéré un enregsitrement
				if ( ! empty( $uploaded_images ) ) {
					foreach ($uploaded_images as $image) {
						$id = $image->post_id;

						$post = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT guid from {$wpdb->prefix}posts where ID = %d",
								$id
							)
						);

						if ( ! empty($post) ) {
							$file = $post[0]->guid;
							$ch = curl_init($file);
							curl_setopt($ch, CURLOPT_NOBODY, true);
							curl_exec($ch);
							$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							curl_close($ch);

							if ($response_code == 200) {
								return $image->post_id;
							} else {
								$wpdb->get_results(
									$wpdb->prepare(
										"DELETE from {$wpdb->prefix}postmeta where meta_id = %d",
										$image->meta_id
									)
								);
								continue;
							}
						}
					}
				}

				return -1;
			}

			/**
			 * Methode get_product_variation
			 *
			 * Récupère une décli d'un produit
			 *
			 * @param int    $id_product : Id du produit dans lequelrécupérer la décli.
			 * @param string $sku : Code-barres de la décli.
			 * @param int    $id_variation (default = 0) : Id de la décli à récupérer si on la connais déjà.
			 */
			protected function get_product_variation( $id_product, $sku, $id_variation = 0 ) {
				global $wpdb;
				
				$retour = false;
				if ( $id_variation > 0 ) {
					$variation = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * from {$wpdb->prefix}posts where {$wpdb->prefix}posts.ID = %d",
							$id_variation
						)
					);
					
					if ( $variation ) {
						$retour = $variation[0];
					}
				} else {
					if ( 0 !== (int)$id_product ) {

						$variation_id = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT `post_id` from {$wpdb->prefix}postmeta
								where {$wpdb->prefix}postmeta.meta_key = '_sku'
								and {$wpdb->prefix}postmeta.meta_value = %s",
								$sku
							)
						);

						$variation = null;

						if ( isset( $variation_id[0]->post_id ) ) {
							$variation = $wpdb->get_results(
								$wpdb->prepare(
									"SELECT * from {$wpdb->prefix}posts where {$wpdb->prefix}posts.ID = %d",
									$variation_id[0]->post_id
								)
							);
						}

						if ( $variation ) {
							if ( count( $variation ) === 1 ) {
								$retour = $variation[0];
							} else {
								$retour = count( $variation ) * -1;
							}
						}
					} else {
						$query = "SELECT * from {$wpdb->prefix}posts
							where {$wpdb->prefix}posts.ID = ( 
								SELECT `post_id` from {$wpdb->prefix}postmeta
								where {$wpdb->prefix}postmeta.meta_key = '_sku'
								where {$wpdb->prefix}postmeta.meta_value = '{$sku}';
							)";

						$variation = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT * from {$wpdb->prefix}posts where {$wpdb->prefix}posts.ID = (
								SELECT `post_id` from {$wpdb->prefix}postmeta
								where {$wpdb->prefix}postmeta.meta_key = '_sku'
								where {$wpdb->prefix}postmeta.meta_value = %s)",
								$sku
							)
						);
						
						if ( $variation ) {
							if ( count( $variation ) === 1 ) {
								$retour = $variation[0];
							} else {
								$retour = count( $variation ) * -1;
							}
						}
						$this->check_timer_send();
					}
				}

				if ( is_object( $retour ) ) {
					$decli                          = array();
					$decli['id']                    = $retour->ID;
					$decli['date_created']          = str_replace( ' ', 'T', $retour->post_date );
					$decli['date_created_gmt']      = str_replace( ' ', 'T', $retour->post_date_gmt );
					$decli['date_modified']         = str_replace( ' ', 'T', $retour->post_modified );
					$decli['date_modified_gmt']     = str_replace( ' ', 'T', $retour->post_modified_gmt );
					$decli['description']           = $this->get_postmeta_value( $retour->ID, '_variation_description' );
					$decli['sku']                   = $this->get_postmeta_value( $retour->ID, '_sku' );
					$decli['price']                 = $this->get_postmeta_value( $retour->ID, '_price' );
					$decli['regular_price']         = $this->get_postmeta_value( $retour->ID, '_regular_price' );
					$decli['sale_price']            = $this->get_postmeta_value( $retour->ID, '_sale_price' );
					$decli['date_on_sale_from']     = gmdate( 'Y-m-d\TH:i:s', strtotime( $this->get_postmeta_value( $retour->ID, '_sale_price_dates_from' ) ) );
					$decli['date_on_sale_from_gmt'] = $this->get_postmeta_value( $retour->ID, '_sale_price_dates_from' );
					$decli['date_on_sale_to']       = gmdate( 'Y-m-d\TH:i:s', strtotime( $this->get_postmeta_value( $retour->ID, '_sale_price_dates_to' ) ) );
					$decli['date_on_sale_to_gmt']   = $this->get_postmeta_value( $retour->ID, '_sale_price_dates_to' );
					$decli['status']                = $retour->post_status;
					$decli['virtual']               = ( 'no' === $this->get_postmeta_value( $retour->ID, '_virtual' ) ) ? '' : 'yes';
					$decli['downloadable']          = ( 'no' === $this->get_postmeta_value( $retour->ID, '_downloadable' ) ) ? '' : 'yes';
					$decli['meta_data']             = array(
						array(
							'id'    => $this->get_postmeta_id( $retour->ID, '_kintpv_id_article' ),
							'key'   => '_kintpv_id_article',
							'value' => $this->get_postmeta_value( $retour->ID, '_kintpv_id_article' ),
						),
					);

					return $decli;
				} else {
					return $retour;
				}
			}

			/**
			 * Methode update_variation
			 *
			 * Mise à jour d'une déclinaison
			 *
			 * @param int   $id_product : Id du produit où se trouve la déclinaison.
			 * @param int   $id : Id de la déclinaison.
			 * @param array $values : Données de la décli à mettre à jour.
			 * @param bool  $visible (default = true) : La décli est active Oui/Non.
			 */
			protected function update_variation( $id_product, $id, $values, $visible = true ) {
				if ( $this->option_checked( 'decli', false ) ) {
					// verification des champs autorisés à etre mis à jour.
					if ( ! $this->option_checked( 'url_simpl', false ) && isset( $values['permalink'] ) ) {
						unset( $values['permalink'] );
					}
					if ( ! $this->option_checked( 'prix_decli', false ) && isset( $values['regular_price'] ) ) {
						unset( $values['regular_price'] );
					}
					if ( ! $this->option_checked( 'poids_decli', false ) && isset( $values['weight'] ) ) {
						unset( $values['weight'] );
					}

					if ( true === $visible ) {
						$values['status'] = 'publish';
					} else {
						$values['status'] = 'private';
					}

					if ( ! isset( $values['image'] ) ) {
						Kintpv_Log::log( 'Mise à jour déclinaison produit : [' . $id_product . '] code barre : [' . $values['sku'] . '] id WC : [' . $id . ']' );
					}

					if ( 0 == $id_product ) {
						$product = $this->get_product_by_variation( $id );

						if ( $product->ID ) {
							$id_product = $product->ID;
						}
					}
					
					$variation = new WC_Product_Variation($id);

					$this->set_variation_data($variation, $values, $id_product);
					
					$updated_variation = $variation->save();

					if ( isset( $updated_variation->code ) ) {
						Kintpv_Log::log( "Appel de l'URL échoué ( PUT ): " . 'products/' . $id_product . '/variations/' . $id );

						Kintpv_Log::log( 'Erreur WooCommerce : ' . $updated_variation->message );
						return false;
					}

					return $updated_variation;
				} else {
					return false;
				}
			}

			/**
			 * Methode create_variation
			 *
			 * Crée une déclinaison d'un produit WooCommerce
			 *
			 * @param int   $id_product : Id du prouit où insérer la décli.
			 * @param array $values : Données de la nouvelle décli.
			 */
			protected function create_variation( $id_product, $values ) {
				if ( $id_product > 0 ) {
					if ( $this->option_checked( 'decli', true ) ) {
						// verification des champs autorisés à etre mis à jour.
						if ( ! $this->option_checked( 'desc_courte', true ) && isset( $values['desc_courte'] ) ) {
							unset( $values['short_description'] );
						}
						if ( ! $this->option_checked( 'url_simpl', true ) && isset( $values['permalink'] ) ) {
							unset( $values['permalink'] );
						}
						if ( ! $this->option_checked( 'prix_decli', true ) && isset( $values['regular_price'] ) ) {
							unset( $values['regular_price'] );
						}
						if ( ! $this->option_checked( 'poids_decli', true ) && isset( $values['weight'] ) ) {
							unset( $values['weight'] );
						}

						Kintpv_Log::log( 'Création déclinaison produit : [' . $id_product . ']  code barre : [' . $values['sku'] . ']' );

						$variation = new WC_Product_Variation(0);

						$this->set_variation_data($variation, $values, $id_product);
						
						$variation->save();

						//TODO: Renvoyer un code d'erreur si !ok

						return $variation;
					}
				}

				return false;
			}

			public function set_variation_data(&$variation, $data, $id_parent)
			{	
				$variation->set_parent_id((int)$id_parent);

				foreach( $data as $key => $val ) {
					switch( $key ) {
						case 'regular_price':
							$variation->set_regular_price($val);
							break;

						case 'sku':
							$variation->set_sku($val);
							break;

						case 'weight':
							$variation->set_weight($val);
							break;

						case 'manage_stock':
							if ( false === $this->m_is_service_hs ) {
								$variation->set_manage_stock($val);
							} else {
								$variation->set_manage_stock(0);
							}
							break;

						case 'stock_quantity':
							if ( $this->m_is_service_hs === false ) {
								$variation->set_stock_quantity($val);
							}
							break;

						case 'attributes':
							$arr_attributes = array();
							$x=0;
							foreach ($val as $attr) {
								$x++;
								$name = wc_get_attribute($attr['id'])->slug;
								$value = $this->slugify($attr['option']);

								$arr_attributes[$name] = $value;
							}

							$variation->set_attributes($arr_attributes);
							$variation->save();
							break;

						case 'meta_data':
							$variation->set_meta_data($val);
							break;

						case 'date_on_sale_from':
							$variation->set_date_on_sale_from($val);
							break;

						case 'date_on_sale_to':
							$variation->set_date_on_sale_to($val);
							break;

						case 'sale_price':
							$variation->set_sale_price($val);
							break;

						case 'status':
							$variation->set_status($val);
							break;

						case 'image':
							$wordpress_upload_dir = wp_upload_dir();
							$new_file_path = $wordpress_upload_dir['path'];

							$insert_id = $this->check_image_exists(WP_PLUGIN_URL.'/kintpv-connect/tmp/'.$val['name']);

							if ($insert_id == -1) {
								$insert_id = $this->insert_image_as_attachment(
									WP_PLUGIN_DIR.'/kintpv-connect/tmp/'.$val['name'],
									WP_PLUGIN_URL.'/kintpv-connect/tmp/'.$val['name'],
									$new_file_path.'/'.$val['name'],
									preg_replace( '/\.[^.]+$/', '', basename( $val['name'] ) ),
									$wordpress_upload_dir['url'] . '/' . basename( $val['name'] )
								);
							}

							$variation->set_image_id($insert_id);
							$variation->save();
							break;
					}
				}

				if ( $this->m_is_service_hs === true ) {
					$variation->set_stock_status('instock');

					$nostock_is_virtual = $this->prefs_nostock_virtual->get( 'is_virtual' );
					if ( null !== $nostock_is_virtual && true === (bool) $nostock_is_virtual->checked ) {
						$variation->set_virtual( true );
					} else if ( null === $nostock_is_virtual || null !== $nostock_is_virtual && false === (bool) $nostock_is_virtual->checked ) {
						$variation->set_virtual( false );
					}
				}
			}

			/**
			 * Methode delete_variation
			 *
			 * Supprime un décli d'un produit
			 *
			 * @param int $id_product : Id du produit concerné.
			 * @param int $id_variation : Id de la déclinaison à supprimer.
			 */
			protected function delete_variation( $id_product, $id_variation ) {
				if ( $id_product > 0 && $id_variation > 0 ) {
					$woocommerce = $this->get_rest_client();

					$del = $woocommerce->delete( 'products/' . $id_product . '/variations/' . $id_variation );
				}
			}

			/**
			 * Methode get_wc_taxes
			 *
			 * Retourne les taxes WooCommerce
			 */
			protected function get_wc_taxes() {
				$retour = false;
				try {
					$retour = $this->wc_get( 'taxes' );
				} catch ( HttpClientException $e ) {
					var_dump( $e->getMessage() );
				}

				return $retour;
			}

			/**
			 * Methode get_wc_classes
			 *
			 * Retourne les classes des taxes WooCommerce
			 */
			protected function get_wc_classes() {
				$retour = false;
				$retour = $this->wc_get( 'taxes/classes' );
				return $retour;
			}

			/**
			 * Methode get_rest_client
			 *
			 * Récupère le client de connexion à l'API de WooCommerce
			 *
			 * @param bool $exit_if_echec (default = true) : Doit-on quitter la synchro si on n'arrive pas à récupérer le client ?.
			 */
			protected function get_rest_client( $exit_if_echec = true ) {
				if ( false === $this->rest_client && '' !== $this->rest_key && '' !== $this->rest_secret ) {
					$wp_url = get_site_url();
					Kintpv_Log::log( 'Ouverture de l\'api rest WC : ' . $wp_url );

					$this->rest_client = new Client(
						$wp_url,
						$this->rest_key,
						$this->rest_secret,
						array(
							'wp_api'            => true,
							'version'           => 'wc/v3',
							'query_string_auth' => true, // Force Basic Authentication as query string true and using under HTTPS.
						)
					);
				}

				if ( ! $this->rest_client ) {
					Kintpv_Log::log( 'Echec identification' );
					if ( $exit_if_echec ) {
						$this->termine( true );
					}
				}
				return $this->rest_client;
			}


			/**
			 * Mehode thermine
			 *
			 * Mets fin au traitement en retournant un erreur
			 *
			 * @param bool   $die (default = false) : Arreter complètement la synchro oui/non ?.
			 * @param string $message_erreur (default = '') : Message supplémentaire à retourner.
			 */
			public function termine( $die = false, $message_erreur = '' ) {
				echo $this->xml_code_error( $this->error, $message_erreur );

				if ( 0 !== $this->error ) {
					Kintpv_Log::log( 'Erreur : ' . $this->error . ' Message : ' . $message_erreur );
				}
				Kintpv_Log::log( '--------------------- FIN ------------------------' );
				echo '</KINTPV>';

				if ( true === $die ) {
					return;
				}
			}

			/**
			 * Methode extract_product_info
			 *
			 * Récupère l'information d'un produit
			 *
			 * @param string $tag : Contenu d'une balise XML.
			 * @param bool   $mode_crea : Est-on en creation ou en modification.
			 * @param int    $id_kin_tpv : Id du produit sur KinTPV.
			 */
			public function extract_product_info( $tag, $mode_crea, &$id_kin_tpv ) {
				$id_kin_tpv = self::get_node_value( $tag, 'IdArticle' );

				$product       = array();
				$product['id'] = self::get_node_value( $tag, 'IdArticleExterne' );

				if ( $this->option_checked( 'nom_produit', $mode_crea ) ) {
					$product['name'] = self::format_denied_chars( self::get_node_value( $tag, 'NomCatalogue' ) );
				}

				/**
				 * Modifié par Jérôme le 08/04/2020
				 *
				 * Si l'option url simplifiée était décochée, la référence de l'article était mise dans l'url du produit
				 *
				 * Désormais si l'option est décochée, on laisse WordPress générer automatiquement le slug
				 */

				if ( $this->option_checked( 'url_simpl', $mode_crea ) ) {
					$product['slug'] = self::get_node_value( $tag, 'URL_Simple' );
				}

				$publie_web = (int) self::get_node_value( $tag, 'PublierWeb_O_N' );

				// Si le produit est coché "publier web" dans KinTPV.
				if ( $publie_web > 0 ) {
					// Et si on est en train de CRÉER l'article dans WordPress.
					// Alors on prends en compte l'état à la création.
					// Sinon on ne touche pas à l'état si l'article est déjà présent.
					if ( $mode_crea ) {
						switch ( $this->prefs_etat_creation->get_value( 1, 'etat' ) ) {
							case 'publish':
								$product['status'] = 'publish';
								break;

							case 'pending':
								$product['status'] = 'pending';
								break;

							case 'draft':
								$product['status'] = 'draft';
								break;

							default:
								$product['status'] = 'publish';
						}
					}
				} else {
					// Si l'article est décoché "publier web" dans KinTPV.
					// On le mets en brouillon.
					$product['status'] = 'draft';
				}

				$product['catalog_visibility'] = 'visible';

				if ( $this->option_checked( 'description', $mode_crea ) ) {
					$v = self::get_node_value( $tag, 'DescriptionComplete', true );
					if ( strip_tags( $v ) === $v ) {
						$v = nl2br( $v );
					}
					$product['description'] = $v;
				}

				if ( $this->option_checked( 'desc_courte', $mode_crea ) ) {
					$v = self::get_node_value( $tag, 'Description', true );
					
					if ( strip_tags( $v ) === $v ) {
						$v = nl2br( $v );
					}

					$product['short_description'] = $v;
				}

				$product['sku'] = self::get_node_value( $tag, 'ArticleCodeBarre' );

				if ( $this->option_checked( 'prix', $mode_crea ) ) {
					$product['regular_price'] = self::format_float( self::get_node_value( $tag, 'PV_BaseTTC' ) );
				}

				$pref_nostock_is_virtual = $this->prefs_nostock_virtual->get( 'is_virtual' );
				$product['virtual'] = ( ( null !== $pref_nostock_is_virtual && true === $pref_nostock_is_virtual->checked ) && (int) self::get_node_value( $tag, 'ServiceHorsStock_O_N' ) > 0 ) ? true : false;
				
				$this->m_is_service_hs = ( (int) self::get_node_value( $tag, 'ServiceHorsStock_O_N' ) > 0 ) ? true : false;

				if ( $this->option_checked( 'poids', $mode_crea ) ) {
					$product['weight'] = self::get_node_value( $tag, 'Poids' );
				}

				if ( $this->option_checked( 'cde_rupture', $mode_crea ) ) {
					$web_backorder = (int) self::get_node_value( $tag, 'WEB_SiRuptureChoix' );
					$product_backorder = '';

					switch ( $web_backorder ) {
						case 0:
							//Récupérer valeur défaut woocommerce
							$product_backorder = 'no';
							break;

						case 1:
							$product_backorder = 'no';
							break;
							
						case 2:
							$product_backorder = 'yes';
							break;
					}

					$product['backorders'] = $product_backorder;
				}

				$product['meta_data'] = array();

				// stock l'id KinTPV dans les meta.
				$product['meta_data'][] = array(
					'key'   => KINTPV_META_ID_ARTICLE,
					'value' => $id_kin_tpv,
				);

				$val = self::get_node_value( $tag, 'MetaKeyword' );
				if ( '' !== $val ) {
					$product['meta_data'][] = array(
						'key'   => 'MetaKeyword',
						'value' => $val,
					);
				}

				$val = self::get_node_value( $tag, 'MetaTitle' );
				if ( '' !== $val ) {
					$product['meta_data'][] = array(
						'key'   => 'MetaTitle',
						'value' => $val,
					);
				}

				$val = self::get_node_value( $tag, 'MetaDescription' );
				if ( '' !== $val ) {
					$product['meta_data'][] = array(
						'key'   => 'MetaDescription',
						'value' => $val,
					);
				}

				// gestion de la classe de taxe.
				if ( $this->option_checked( 'taxes', $mode_crea ) ) {
					$val = self::get_node_value( $tag, 'PV_IdTauxTaxe' );
					if ( $val ) {
						$taxe = $this->prefs_taxe->get( (int) $val );

						if ( $taxe ) {
							Kintpv_Log::log( 'Produit TVA KinTPV : ' . $val . ' Classe taxe WC  : ' . $taxe->wc_idc );
							$product['tax_class']  = $taxe->wc_idc;
							$product['tax_status'] = 'taxable';
						} else {
							Kintpv_Log::log( 'Produit TVA KinTPV : ' . $val . ' Pas de classe équivalente' );
						}
					}
				}

				$product['type'] = 'simple';

				return $product;
			}

			/**
			 * Méthode protduct_to_variation
			 *
			 * Transforme un produit en une déclinaison
			 *
			 * @param array $product : Produit à transformer.
			 */
			public function product_to_variation( &$product ) {
				$variation                  = array();
				$variation['description']   = $product['description'];
				$variation['permalink']     = ( isset( $product['permalink'] ) ) ? $product['permalink'] : '';
				$variation['sku']           = $product['sku'];
				$variation['regular_price'] = $product['regular_price'];
				$variation['visible']       = ( isset( $product['status'] ) && 'publish' === $product['status'] ) ? true : false;
				$variation['virtual']       = $product['virtual'];
				$variation['weight']        = $product['weight'];
				$variation['meta_data']     = $product['meta_data'];

				Kintpv_Log::log( 'Conversion de produit en déclinaison : [' . $product['name'] . '] ref : [' . $product['slug'] . '] code barre : [' . $product['sku'] . ']' );

				return $variation;
			}

			/**
			 * Méthode shipping_list
			 *
			 * Retourne la liste des transporteurs
			 */
			public function shipping_list() {
				$zones  = $this->wc_get( 'shipping/zones' );
				$retour = array();
				$cpt    = 0;

				foreach ( $zones as $z ) {
					$retour[ $cpt ]['id_zone'] = $z->id;
					$retour[ $cpt ]['nom']     = $z->name;
					$retour[ $cpt ]['methods'] = $this->wc_get( 'shipping/zones/' . $z->id . '/methods' );
					$cpt++;
				}

				return $retour;
			}

			/**
			 * Métthode payment_list
			 *
			 * Retourne la liste des modes de paiement
			 */
			public function payment_list() {
				return $this->wc_get( 'payment_gateways' );
			}

			/**
			 * Méthode get_categorie
			 *
			 * Méthode de récupération d'une catégorie
			 *
			 * @param int $id_wc : Id WooCommerce de la catégorie recherchée.
			 * @param int $id_kin_tpv : Id provenant de KinTPV à rechercher.
			 */
			public function get_categorie( $id_wc, $id_kin_tpv = '' ) {
				$categorie = null;
				if ( '' !== $id_kin_tpv ) {
					$data       = array( 'slug' => $id_kin_tpv );
					$categories = $this->wc_get( 'products/categories', $data );

					if ( $categories ) {
						$categorie = $categories;
						return $categorie;
					}
				} else {
					$categorie = $this->wc_get( 'products/categories/' . (int) $id_wc );
				}
				return $categorie;
			}

			/**
			 * Méthode create_categorie
			 *
			 * Crée une nouvelle catégorie
			 *
			 * @param array $data : Informations de la nouvelle catégorie.
			 */
			public function create_categorie( $data ) {
				$woocommerce = $this->get_rest_client();

				Kintpv_Log::log( 'Création de la catégorie : ' . $data['name'] . ' idKinTPV : ' . $data['slug'] );

				$cat = $woocommerce->post( 'products/categories', $data );

				if ( isset( $cat->code ) ) {
					Kintpv_Log::log( "Appel de l'URL échoué ( POST ) : " . 'products/categories' );

					Kintpv_Log::log( 'Erreur WooCommerce : ' . $cat->message );
				}

				return $cat;
			}

			/**
			 * Méthode update_categorie
			 *
			 * Méthode de mise à jour d'une catégorie
			 *
			 * @param int   $id : Id de la catégorie à mettre à jour.
			 * @param array $data : Valeurs à mettre à jour.
			 */
			public function update_categorie( $id, $data ) {
				$woocommerce = $this->get_rest_client();
				Kintpv_Log::log( 'Mise à jour de la catégorie : "' . $data['name'] . '" -  id KinTPV : ' . $data['id_kin'] . ' - id WC : ' . $id );

				$updated_categorie = $woocommerce->put( 'products/categories/' . $id, $data );

				return $updated_categorie;
			}

			/**
			 * Gestion des commandes client
			 *
			 * @param int $start_id : Id de départ de recherche des commandes.
			 * @param int $id : Id de recherche précis d'une commande.
			 */
			public function get_commandes( int $start_id = 0, $id = 0 ) {
				set_time_limit( 0 );
				$commandes = array();

				/**
				 * On récupère la date correspondant à l'id de la dernière commande envoyé par KinTPV
				 * Qui sera mis dans le paramètre 'after' de la requete permettant de ne pas récupérer les commandes
				 * déjà envoyées.
				 */

				global $wpdb;

				$pref_nb_orders_by_sync = $this->prefs_synchro_commande->get( 'orders_by_sync' );

				$nb_orders_by_sync = ( null !== $pref_nb_orders_by_sync && (int) $pref_nb_orders_by_sync->value > 0 ) ? (int) $pref_nb_orders_by_sync->value
				: KINTPV_DEFAULT_ORDER_BY_SYNC;

				if ( 0 === $id ) {
					$get_commandes = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * from {$wpdb->prefix}posts
							where {$wpdb->prefix}posts.post_type = 'shop_order'
							and {$wpdb->prefix}posts.post_status != 'trash'
							and {$wpdb->prefix}posts.ID > %d
							order by {$wpdb->prefix}posts.ID asc
							limit %d",
							$start_id,
							$nb_orders_by_sync
						)
					);
				} else {
					$get_commandes = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * from {$wpdb->prefix}posts
							where {$wpdb->prefix}posts.ID = %d",
							$id
						)
					);
				}

				foreach ( $get_commandes as $cmd ) {
					$cmd_temp                       = array();
					$cmd_temp['id']                 = $cmd->ID;
					$cmd_temp['parent_id']          = $cmd->post_parent;
					$cmd_temp['status']             = str_replace( 'wc-', '', $cmd->post_status );
					$cmd_temp['currency']           = $this->get_postmeta_value( $cmd->ID, '_order_currency' );
					$cmd_temp['version']            = $this->get_postmeta_value( $cmd->ID, '_order_version' );
					$cmd_temp['prices_include_tax'] = ( 'yes' === $this->get_postmeta_value( $cmd->ID, '_prices_include_tax' ) ) ? 1 : '';
					$cmd_temp['date_created']       = gmdate( 'Y-m-d\TH:i:s', strtotime( $cmd->post_date ) );
					$cmd_temp['date_modified']      = gmdate( 'Y-m-d\TH:i:s', strtotime( $cmd->post_modified ) );
					$cmd_temp['discount_total']     = number_format( (int) $this->get_postmeta_value( $cmd->ID, '_cart_discount' ), 2, '.', '' );
					$cmd_temp['discount_tax']       = number_format( (int) $this->get_postmeta_value( $cmd->ID, '_cart_discount_tax' ), 2, '.', '' );
					$cmd_temp['shipping_total']     = number_format( (int) $this->get_postmeta_value( $cmd->ID, '_order_shipping' ), 2, '.', '' );
					$cmd_temp['shipping_tax']       = number_format( (int) $this->get_postmeta_value( $cmd->ID, '_order_shipping_tax' ), 2, '.', '' );
					$cmd_temp['cart_tax']           = '';
					$cmd_temp['total']              = number_format( (float) $this->get_postmeta_value( $cmd->ID, '_order_total' ), 2, '.', '' );
					$cmd_temp['total_tax']          = number_format( (float) $this->get_postmeta_value( $cmd->ID, '_order_tax' ), 2, '.', '' );
					$cmd_temp['customer_id']        = $this->get_postmeta_value( $cmd->ID, '_customer_user' );
					$cmd_temp['order_key']          = $this->get_postmeta_value( $cmd->ID, '_order_key' );
					$cmd_temp['billing']            = array(
						'first_name' => (string) $this->get_postmeta_value( $cmd->ID, '_billing_first_name' ),
						'last_name'  => (string) $this->get_postmeta_value( $cmd->ID, '_billing_last_name' ),
						'company'    => (string) $this->get_postmeta_value( $cmd->ID, '_billing_company' ),
						'address_1'  => (string) $this->get_postmeta_value( $cmd->ID, '_billing_address_1' ),
						'address_2'  => (string) $this->get_postmeta_value( $cmd->ID, '_billing_address_2' ),
						'city'       => (string) $this->get_postmeta_value( $cmd->ID, '_billing_city' ),
						'state'      => (string) $this->get_postmeta_value( $cmd->ID, '_billing_state' ),
						'postcode'   => (string) $this->get_postmeta_value( $cmd->ID, '_billing_postcode' ),
						'country'    => (string) $this->get_postmeta_value( $cmd->ID, '_billing_country' ),
						'email'      => (string) $this->get_postmeta_value( $cmd->ID, '_billing_email' ),
						'phone'      => (string) $this->get_postmeta_value( $cmd->ID, '_billing_phone' ),
					);

					$cmd_temp['shipping'] = array(
						'first_name' => (string) $this->get_postmeta_value( $cmd->ID, '_shipping_first_name' ),
						'last_name'  => (string) $this->get_postmeta_value( $cmd->ID, '_shipping_last_name' ),
						'company'    => (string) $this->get_postmeta_value( $cmd->ID, '_shipping_company' ),
						'address_1'  => (string) $this->get_postmeta_value( $cmd->ID, '_shipping_address_1' ),
						'address_2'  => (string) $this->get_postmeta_value( $cmd->ID, '_shipping_address_2' ),
						'city'       => (string) $this->get_postmeta_value( $cmd->ID, '_shipping_city' ),
						'state'      => (string) $this->get_postmeta_value( $cmd->ID, '_shipping_state' ),
						'postcode'   => (string) $this->get_postmeta_value( $cmd->ID, '_shipping_postcode' ),
						'country'    => (string) $this->get_postmeta_value( $cmd->ID, '_shipping_country' ),
						'phone'      => (string) $this->get_postmeta_value( $cmd->ID, '_shipping_phone' ),
					);

					$cmd_temp['payment_method']       = $this->get_postmeta_value( $cmd->ID, '_payment_method' );
					$cmd_temp['payment_method_title'] = (string) $this->get_postmeta_value( $cmd->ID, '_payment_method_title' );
					$cmd_temp['transaction_id']       = $this->get_postmeta_value( $cmd->ID, '_transaction_id' );
					$cmd_temp['customer_ip_address']  = '';
					$cmd_temp['customer_user_agent']  = '';
					$cmd_temp['created_via']          = $this->get_postmeta_value( $cmd->ID, '_created_via' );
					$cmd_temp['customer_note']        = $cmd->post_excerpt;
					$cmd_temp['date_completed']       = gmdate( 'Y-m-d\TH:i:s', strtotime( $this->get_postmeta_value( $cmd->ID, '_completed_date' ) ) );
					$cmd_temp['date_paid']            = gmdate( 'Y-m-d\TH:i:s', strtotime( $this->get_postmeta_value( $cmd->ID, '_paid_date' ) ) );
					$cmd_temp['cart_hash']            = '';
					$cmd_temp['number']               = $cmd->ID;
					$cmd_temp['meta_data']            = array();
					$cmd_temp['line_items']           = array();
					$cmd_temp['tax_lines']            = array();
					$cmd_temp['shipping_lines']       = array();
					$cmd_temp['fee_lines']            = array();
					$cmd_temp['coupon_lines']         = array();
					$cmd_temp['refunds']              = array();
					$cmd_temp['date_created_gmt']     = $cmd->post_date_gmt;
					$cmd_temp['date_modified_gmt']    = $cmd->post_modified_gmt;
					$cmd_temp['date_completed_gmt']   = '';
					$cmd_temp['date_paid_gmt']        = '';
					$cmd_temp['currency_symbol']      = ( 'EUR' === $this->get_postmeta_value( $cmd->ID, '_order_currency' ) ) ? '€' : ( ( 'USD' === $this->get_postmeta_value( $cmd->ID, '_order_currency' ) ) ? '$' : '' );

					$array_shipping_lines = array();
					$cmd_shipping         = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * from {$wpdb->prefix}woocommerce_order_items where order_id = %d and order_item_type = 'shipping'",
							$cmd->ID
						)
					);

					foreach ( $cmd_shipping as $shipping ) {
						$shipping_temp                 = array();
						$shipping_temp['id']           = (int) $shipping->order_item_id;
						$shipping_temp['method_title'] = $shipping->order_item_name;
						$shipping_temp['method_id']    = $this->get_orderitem_postmeta_value( $shipping->order_item_id, 'method_id' );
						$shipping_temp['instance_id']  = $this->get_orderitem_postmeta_value( $shipping->order_item_id, 'instance_id' );
						$shipping_temp['total']        = number_format( $this->get_orderitem_postmeta_value( $shipping->order_item_id, 'cost' ), 2, '.', '' );
						$shipping_temp['total_tax']    = number_format( $this->get_orderitem_postmeta_value( $shipping->order_item_id, 'total_tax' ), 2, '.', '' );

						// Récupération tax shipping line.
						$taxes = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT * from {$wpdb->prefix}woocommerce_order_items where order_id = %d and order_item_type = 'tax'",
								$cmd->ID
							)
						);

						foreach ( $taxes as $tax ) {
							$tax_temp = array();

							$tax_temp['id']           = $this->get_orderitem_postmeta_value( $tax->order_item_id, 'rate_id' );
							$tax_temp['total']        = number_format( $this->get_orderitem_postmeta_value( $tax->order_item_id, 'shipping_tax_amount' ), 2, '.', '' );
							$shipping_temp['taxes'][] = $tax_temp;
						}

						$array_shipping_lines[] = $shipping_temp;
					}

					$cmd_temp['shipping_lines'] = $array_shipping_lines;

					$array_items = array();
					$cmd_items   = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * from {$wpdb->prefix}woocommerce_order_items where order_id = %d and order_item_type = 'line_item'",
							$cmd->ID
						)
					);

					foreach ( $cmd_items as $item ) {
						$item_temp                 = array();
						$item_temp['id']           = $item->order_item_id;
						$item_temp['name']         = $item->order_item_name;
						$item_temp['product_id']   = $this->get_orderitem_postmeta_value( $item->order_item_id, '_product_id' );
						$item_temp['variation_id'] = $this->get_orderitem_postmeta_value( $item->order_item_id, '_variation_id' );
						$item_temp['quantity']     = $this->get_orderitem_postmeta_value( $item->order_item_id, '_qty' );
						$item_temp['reference']    = '';
						$item_temp['taxes']        = array();
						$item_temp['tax_class']    = $this->get_orderitem_postmeta_value( $item->order_item_id, '_tax_class' );
						$item_temp['subtotal']     = number_format( $this->get_orderitem_postmeta_value( $item->order_item_id, '_line_subtotal' ), 2, '.', '' );
						$item_temp['subtotal_tax'] = number_format( $this->get_orderitem_postmeta_value( $item->order_item_id, '_line_subtotal_tax' ), 2, '.', '' );
						$item_temp['total']        = number_format( $this->get_orderitem_postmeta_value( $item->order_item_id, '_line_total' ), 2, '.', '' );
						$item_temp['total_tax']    = number_format( $this->get_orderitem_postmeta_value( $item->order_item_id, '_line_tax' ), 2, '.', '' );
						$item_temp['sku']          = ( $item_temp['variation_id'] > 0 ) ? $this->get_postmeta_value( $item_temp['variation_id'], '_sku' ) : $this->get_postmeta_value( $item_temp['product_id'], '_sku' );

						$taxes = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT * from {$wpdb->prefix}woocommerce_order_items where order_id = %d and order_item_type = 'tax'",
								$cmd->ID
							)
						);

						foreach ( $taxes as $tax ) {
							$tax_temp = array();

							$tax_temp['id']       = $this->get_orderitem_postmeta_value( $tax->order_item_id, 'rate_id' );
							$tax_temp['total']    = number_format( $this->get_orderitem_postmeta_value( $tax->order_item_id, 'shipping_tax_amount' ), 2, '.', '' );
							$item_temp['taxes'][] = $tax_temp;
						}

						$array_items[] = $item_temp;
					}
					$cmd_temp['line_items'] = $array_items;
					$commandes[]            = $cmd_temp;
				}

				return $commandes;
			}

			/**
			 * Méthode get_postmeta_id
			 *
			 * Retourne l'id d'une meta value d'un d'une clé spécifique à un post
			 *
			 * @param int    $id_post : Id du post.
			 * @param string $key : Clé pour laquelle récupérer la valeur meta.
			 */
			public function get_postmeta_id( $id_post, $key ) {
				global $wpdb;
				$val = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT `meta_id` from {$wpdb->prefix}postmeta where post_id = %d and meta_key = %s",
						$id_post,
						$key
					)
				);

				$return = ( ! empty( $val ) && isset( $val[0]->meta_id ) ) ? $val[0]->meta_id : 0;
				return $return;
			}

			/**
			 * Méthode get_postmeta_value
			 *
			 * Retourne une valeur dans postmeta pour un post spécifique
			 *
			 * @param int    $id_post : Id du post concerné.
			 * @param string $key : Nom de la valeur à retourner.
			 */
			public function get_postmeta_value( $id_post, $key ) {
				global $wpdb;
				$val = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT `meta_value` from {$wpdb->prefix}postmeta where post_id = %d and meta_key = %s",
						$id_post,
						$key
					)
				);

				$return = ( ! empty( $val ) && isset( $val[0]->meta_value ) ) ? $val[0]->meta_value : '';
				return $return;
			}

			/**
			 * Méthode get_orderitem_postmeta_value
			 *
			 * Récupère une valeur de order_itemmeta pour une commande spécifique
			 *
			 * @param int    $id_post : Id de la commande.
			 * @param string $key : Nom de la valeur à récupérer.
			 */
			public function get_orderitem_postmeta_value( $id_post, $key ) {
				global $wpdb;
				$val = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT `meta_value` from {$wpdb->prefix}woocommerce_order_itemmeta where order_item_id = %d and meta_key = %s",
						$id_post,
						$key
					)
				);

				$return = ( ! empty( $val ) && isset( $val[0]->meta_value ) ) ? $val[0]->meta_value : '';
				return $return;
			}

			/**
			 * Methode get_order_item
			 *
			 * Retourne une valeur de order_item pour une commande spécifique
			 *
			 * @param int $id_post : Id du post cherché.
			 */
			public function get_order_item( $id_post ) {
				global $wpdb;
				$val = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * from {$wpdb->prefix}woocommerce_order_items where order_id = %d and order_item_type = 'line_item'",
						$id_post
					)
				);

				return $val;
			}

			/**
			 * Méthode get_refunds
			 *
			 * Retourne les commandes remboursées
			 */
			public function get_refunds() {
				$ret_date = substr( $this->last_ret_date, 0, -5 ) . '00:00';

				$array_returns = array();

				global $wpdb;

				$pref_nb_orders_by_sync = $this->prefs_synchro_commande->get( 'orders_by_sync' );

				$nb_orders_by_sync = ( null !== $pref_nb_orders_by_sync && (int) $pref_nb_orders_by_sync->value > 0 ) ? (int) $pref_nb_orders_by_sync->value
				: KINTPV_DEFAULT_ORDER_BY_SYNC;

				$order_refunds = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT post_parent from {$wpdb->prefix}posts
						where {$wpdb->prefix}posts.post_type = 'shop_order_refund'
						and {$wpdb->prefix}posts.post_status != 'trash'
						and {$wpdb->prefix}posts.ID > %d
						LIMIT %d",
						$this->last_ret_received,
						$nb_orders_by_sync
					)
				);

				$refunds = array();

				foreach ( $order_refunds as $order_refund ) {
					$refund = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * from {$wpdb->prefix}posts
							where {$wpdb->prefix}posts.post_status != 'trash'
							and {$wpdb->prefix}posts.ID = %d",
							$order_refund->post_parent
						)
					);

					if ( is_array( $refund ) && isset( $refund[0] ) ) {
						$refunds[] = $refund[0];
					}
				}

				foreach ( $refunds as $ref ) {
					$ref_temp                     = array();
					$ref_temp['id']               = $ref->ID;
					$ref_temp['date_created']     = str_replace( ' ', 'T', $ref->post_date );
					$ref_temp['date_created_gmt'] = str_replace( ' ', 'T', $ref->post_date_gmt );
					$ref_temp['amount']           = $this->get_postmeta_value( $ref->ID, '_refund_amount' );
					$ref_temp['reason']           = $this->get_postmeta_value( $ref->ID, '_refund_reason' );
					$ref_temp['refunded_by']      = $this->get_postmeta_value( $ref->ID, '_refunded_by' );
					$ref_temp['refunded_payment'] = $this->get_postmeta_value( $ref->ID, '_refunded_payment' );
					$ref_temp['line_items']       = array();

					$ref_temp['order'] = $this->get_commandes( 0, $ref->ID )[0];

					$array_line_items = array();

					$refund_items = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * from {$wpdb->prefix}posts
							where {$wpdb->prefix}posts.post_parent = %d",
							$ref->ID
						)
					);

					foreach ( $refund_items as $item ) {
						$order_item = $this->get_order_item( $item->ID );

						if ( ! empty( $order_item ) ) {
							$order_item_id = $order_item[0]->order_item_id;

							$line_product = null;
							$line_product_id = 0;
							if ($this->get_orderitem_postmeta_value( $order_item_id, '_variation_id') > 0) {
								$line_product_id = $this->get_orderitem_postmeta_value( $order_item_id, '_variation_id');
							} else {
								$line_product_id = $this->get_orderitem_postmeta_value( $order_item_id, '_product_id');
							}
							$line_product = wc_get_product($line_product_id);

							$temp_line_item                 = array();
							$temp_line_item['id']           = $order_item_id;
							$temp_line_item['name']         = $order_item[0]->order_item_name;
							$temp_line_item['product_id']   = $this->get_orderitem_postmeta_value( $order_item_id, '_product_id' );
							$temp_line_item['variation_id'] = $this->get_orderitem_postmeta_value( $order_item_id, '_variation_id' );
							$temp_line_item['quantity']     = $this->get_orderitem_postmeta_value( $order_item_id, '_qty' );
							$temp_line_item['reference']    = '';
							$temp_line_item['taxes']        = array();
							$temp_line_item['tax_class']    = $this->get_orderitem_postmeta_value( $order_item_id, '_tax_class' );
							$temp_line_item['subtotal']     = number_format( $this->get_orderitem_postmeta_value( $order_item_id, '_line_subtotal' ), 2, '.', '' );
							$temp_line_item['subtotal_tax'] = number_format( $this->get_orderitem_postmeta_value( $order_item_id, '_line_subtotal_tax' ), 2, '.', '' );
							$temp_line_item['total']        = number_format( $this->get_orderitem_postmeta_value( $order_item_id, '_line_total' ), 2, '.', '' );
							$temp_line_item['total_tax']    = number_format( $this->get_orderitem_postmeta_value( $order_item_id, '_line_tax' ), 2, '.', '' );
							$temp_line_item['sku']          = ( $line_product != false ) ? $line_product->sku : '';

							if ($temp_line_item['quantity'] == 0) {
								continue;
							}

							$taxes = $wpdb->get_results(
								$wpdb->prepare(
									"SELECT * from {$wpdb->prefix}woocommerce_order_items where order_id = %d and order_item_type = 'tax'",
									$ref->ID
								)
							);

							foreach ( $taxes as $tax ) {
								$tax_temp          = array();
								$tax_temp['id']    = $this->get_orderitem_postmeta_value( $tax->order_item_id, 'rate_id' );
								$tax_temp['total'] = number_format( $this->get_orderitem_postmeta_value( $tax->order_item_id, 'shipping_tax_amount' ), 2, '.', '' );

								$temp_line_item['taxes'][] = $tax_temp;
							}
							
							$array_line_items[] = $temp_line_item;
						}
					}

					if ( ! empty( $array_line_items ) ) {
						$ref_temp['line_items'] = $array_line_items;
						
						$array_returns[ $ref->ID ] = $ref_temp;
					}
				}

				return $array_returns;
			}

			/**
			 * Méthode DELETE_LOGS
			 *
			 * Méthode de suppression du fichier kintpv.log du module
			 */
			protected function delete_logs() {
				$log_file = Kintpv_Log::log_path() . '/' . KINTPV_FICHIER_LOG;
				if ( file_exists( $log_file ) ) {
					echo (bool) unlink( $log_file );
				} else {
					return true;
				}
			}
		}

		try {
			new KinTPVConnect();
		} catch ( Exception $e ) {
			Kintpv_Log::log( $e->getMessage() );
		}
	}
}