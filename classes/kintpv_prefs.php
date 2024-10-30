<?php

class Kintpv_Prefs {

	protected $prefs;

	public function __construct( $serialized_string ) {
		$this->prefs = unserialize( $serialized_string );
	}

	public function set( $kpref ) {
		$indice = $this->find( $kpref->id );
		if ( $indice >= 0 ) {
			$this->prefs[ $indice ] = $kpref;
		} else {
			$this->prefs[] = $kpref;
		}
	}

	public function get_value( $id, $name ) {
		$indice = $this->find( $id );

		if ( $indice >= 0 ) {
			$obj = $this->prefs[ $indice ];
			if ( isset( $obj->$name ) ) {
				return $obj->$name;
			}
		}
		return '';
	}

	public function get( $id ) {
		$indice = $this->find( $id );

		if ( $indice >= 0 ) {
			return $this->prefs[ $indice ];
		} else {
			return false;
		}
	}

	protected function find( $id ) {
		$indice = -1;

		if ( is_countable( $this->prefs ) ) {
			for ( $i = 0; $i < count( $this->prefs ); $i++ ) {
				if ( $this->prefs[ $i ]->id === $id ) {
					$indice = $i;
				}
			}
		}

		return $indice;
	}

	public function search( $name, $value ) {
		$id = 0;
		for ( $i = 0; $i < count( $this->prefs ) && 0 === $id; $i++ ) {
			if ( isset( $this->prefs[ $i ]->$name ) ) {
				if ( $this->prefs[ $i ]->$name === $value ) {
					$id = $this->prefs[ $i ]->id;
				}
			}
		}
		return $id;
	}

	public function serialize() {
		return serialize( $this->prefs );
	}

	public function get_liste() {
		return $this->prefs;
	}
}
