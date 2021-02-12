<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class OIDplusHost extends OIDplusObject {
	private $host;

	public function __construct($host) {
		// TODO: syntax checks
		$this->host = $host;
	}

	public static function parse($node_id) {
		if(false===strpos($node_id, ':')){
			return false;
		}
		list($namespace, $host) = explode(':', $node_id, 2);
		if ($namespace !== 'host') return false;
		$h = explode('.', $host);
		$h = array_reverse($h);
		$host = implode('.', $h);
	
		return new self($host);
	}

	public static function objectTypeTitle() {
		return _L('Hostname (Domain)');
	}

	public static function objectTypeTitleShort() {
		return _L('Host');
	}

	public static function ns() {
		return 'host';
	}

	public static function root() {
		return 'host:';
		//return 'oid:1.3.6.1.4.1.37553.8.9.17704';
	}

	public function isRoot() {
	//	$rdns = explode('.', $this->host);
		//die($this->host );
		return $this->host == ''; //|| count($rdns) <=2;
	}

	public function nodeId($with_ns=true) {
/*	
	$resultId = $with_ns ? 'host:'.$this->host : $this->host;
		die($resultId);
		*/		
		$i = explode(':', $this->host, 3);
		$i = (count($i) > 1 ) ? $i[1] : $i[0];
		$i=explode('.',$i);
		$p = array_reverse($i);
		$id = implode('.', $p); 
		$resultId = $with_ns ? self::ns().':'.$id :$id;		
		
		return $resultId;		
	}

	public function addString($str) {
		if ($this->isRoot()) {
			return 'host:'.$str;
		} else {
			if (strpos($str,'.') !== false) throw new OIDplusException(_L('Please only submit one arc.'));
			//return $this->nodeId() . '.' . $str;
			return self::ns().':'.$str . '.' . $this->nodeId(false);
		}
	}

	public function crudShowId(OIDplusObject $parent) {
	//	return $parent->host;
	//	die(print_r($parent, true));
	//	return $this->host;
		return $this->nodeId(false);
	}

	public function crudInsertPrefix() {
		return $this->isRoot() ? '' : substr($this->addString(''), strlen(self::ns())+1);
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		return implode('.', array_reverse(explode('.', $this->host)));
	}

	public function defaultTitle() {
	
		return $this->host;
	}

	public function isLeafNode() {
		return false;
	}

	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/icon_big.png') ? 'plugins/objectTypes/'.basename(__DIR__).'/icon_big.png' : '';

		if ($this->isRoot()) {
			$title = OIDplusHost::objectTypeTitle();

			$res = OIDplus::db()->query("select * from ###objects where parent = ?", array(self::root()));
			if ($res->num_rows() > 0) {
				$content  = _L('Please select a host Name in the tree view at the left to show its contents.');
			} else {
				$content  = _L('Currently, no host Name is registered in the system.');
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
					$content .= '<h2>'._L('Manage root hosts').'</h2>';
				} else {
					$content .= '<h2>'._L('Available hosts').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$title = $this->getTitle();

			$content = '<h3>'.explode(':',$this->nodeId())[1].'</h3>';

			$content .= '<h2>'._L('Description').'</h2>%%DESC%%'; // TODO: add more meta information about the object type

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>'._L('Create or change subsequent hosts').'</h2>';
				} else {
					$content .= '<h2>'._L('Subsequent hosts').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		}
	}

	public function one_up() {
		$oid = $this->host;
	
		
		$p = strrpos($oid, '.');
		if ($p === false) return $oid;
		if ($p == 0) return '.';

		$oid_up = substr($oid, 0, $p);

		return self::parse(self::ns().':'.$oid_up);
	}

	public function distance($to) {
		if (!is_object($to)) $to = self::parse($to);
		if (!($to instanceof $this)) return false;

		$a = $to->host;
		$b = $this->host;

		if (substr($a,0,1) == '.') $a = substr($a,1);
		if (substr($b,0,1) == '.') $b = substr($b,1);

		$ary = explode('.', $a);
		$bry = explode('.', $b);

		$min_len = min(count($ary), count($bry));

		for ($i=0; $i<$min_len; $i++) {
			if ($ary[$i] != $bry[$i]) return false;
		}

		return count($ary) - count($bry);
	}
	
	public function getAltIds() {
		if ($this->isRoot()) return array();
		$ids = parent::getAltIds();
		//print_r($ids);
		/*
		if ($uuid = oid_to_uuid($this->oid)) {
			$ids[] = new OIDplusAltId('guid', $uuid, _L('GUID representation of this OID'));
		}
		$ids[] = new OIDplusAltId('guid', gen_uuid_md5_namebased(UUID_NAMEBASED_NS_OID, $this->oid), _L('Name based version 3 / MD5 UUID with namespace %1','UUID_NAMEBASED_NS_OID'));
		$ids[] = new OIDplusAltId('guid', gen_uuid_sha1_namebased(UUID_NAMEBASED_NS_OID, $this->oid), _L('Name based version 5 / SHA1 UUID with namespace %1','UUID_NAMEBASED_NS_OID'));
		*/
		return $ids;
	}	
}
