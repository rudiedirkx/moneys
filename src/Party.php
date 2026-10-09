<?php

namespace rdx\moneys;

class Party extends Model {
	static public $_table = 'parties';

	static public function presave( array &$data ) : void {
		parent::presave($data);

		if ( isset($data['category_id']) && empty($data['category_id']) ) {
			$data['category_id'] = null;
		}
	}
}
