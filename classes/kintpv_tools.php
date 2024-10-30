<?php

class Kintpv_Tools {

	public static function creer_dossier( $dossier ) {
		if ( false === is_dir( $dossier ) ) {
			if ( false === mkdir( $dossier ) ) {
				Kintpv_Log::log( 'Impossible de créer le dossier : ' . $dossier );
				return KINTPV_ERROR_FOLDER_CREATION;
			}
		}

		return 0;
	}
}
