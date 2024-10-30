<?php

class Kintpv_Log {

	public static $handle_log = null;

	protected static function open() {
		$retour = false;
		if ( null === self::$handle_log ) {
			$dossier = self::log_path();

			$error = Kintpv_Tools::creer_dossier( $dossier );

			if ( 0 !== $error ) {
				return $error;
			}

			self::$handle_log = fopen( $dossier . '/' . KINTPV_FICHIER_LOG, 'a' );

			if ( self::$handle_log ) {
				$retour = true;
			}
		} else {
			$retour = true;
		}

		return $retour;
	}

	public static function close() {
		if ( self::$handle_log ) {
			fclose( self::$handle_log );
		}
	}

	public static function log( $content ) {
		if ( self::open() ) {

			// il faut compléter le contenu.
			// au debut on met la date et l'heure.
			date_default_timezone_set('Europe/Paris');
			$prefix = date( 'Y-m-d H:i:s' ) . ' : ';
			fwrite( self::$handle_log, $prefix . $content . "\r\n" );
		}
	}

	public static function log_path() {
		$plugin_dir = ABSPATH . 'wp-content/plugins/kintpv-connect/';
		return $plugin_dir . KINTPV_DOSSIER_LOG;
	}
}
