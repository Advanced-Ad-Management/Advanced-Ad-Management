<?php
	/*
     *      OSCLass – software for creating and publishing online classified
     *                           advertising platforms
     *
     *                        Copyright (C) 2010 OSCLASS
     *
     *       This program is free software: you can redistribute it and/or
     *     modify it under the terms of the GNU Affero General Public License
     *     as published by the Free Software Foundation, either version 3 of
     *            the License, or (at your option) any later version.
     *
     *     This program is distributed in the hope that it will be useful, but
     *         WITHOUT ANY WARRANTY; without even the implied warranty of
     *        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     *             GNU Affero General Public License for more details.
     *
     *      You should have received a copy of the GNU Affero General Public
     * License along with this program.  If not, see <http://www.gnu.org/licenses/>.
     */

    /**
     * Model database for osclass PM tables
     *
     * @package OSClass
     * @subpackage Model
     * @1.0
     */
    class ModelAAM extends DAO
    {
        /**
         * It references to self object: ModelAAM.
         * It is used as a singleton
         *
         * @access private
         * @1.0
         * @var ModelAAM
         */
        private static $instance ;

        /**
         * It creates a new ModelAAM object class ir if it has been created
         * before, it return the previous object
         *
         * @access public
         * @1.0
         * @return ModelAAM
         */
        public static function newInstance()
        {
            if( !self::$instance instanceof self ) {
                self::$instance = new self ;
            }
            return self::$instance ;
        }

        /**
         * Construct
         */
        function __construct()
        {
            parent::__construct();
        }

        /**
         * Return table name adMan_ads
         * @return string
         */
        public function getTable_aam_limit()
        {
            return DB_TABLE_PREFIX.'t_item_adManage_limit';
        }

        /**
         * Return table name adMan_ads
         * @return string
         */
        public function getTable_aam_log()
        {
            return DB_TABLE_PREFIX.'t_item_adManage_log';
        }

        /**
         * Import sql file
         * @param type $file
         */
        public function import($file)
        {
            $path = osc_plugin_resource($file) ;
            $sql = file_get_contents($path);

            if(! $this->dao->importSQL($sql) ){
                throw new Exception( "Error importSQL::ModelAAM<br>".$file ) ;
            }
        }

        /**
         * Remove data and tables related to the plugin.
         */
        public function uninstall()
        {
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_aam_limit()) ) ;
            $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_aam_log()) ) ;
        }

        /**
         * get limit row by user.
         *
         * @return array
         */
        public function getLimitUser($itemId, $secretCheck = false, $secretKey = null)
        {
            $this->dao->select();
            $this->dao->from($this->getTable_aam_limit() );
            $this->dao->where('fk_i_item_id', $itemId);
            if($secretCheck){
				$this->dao->where('r_secret',$secretKey);
			}

            $result = $this->dao->get();
            if($result == NULL){
				return NULL;
			} else {
				return $result->row();
			}
        }

        /**
         * count Log entries
         *
         * @return array
         */
        public function countLog($endLimit = 0)
        {
			$this->dao->select();
            $this->dao->from($this->getTable_aam_log() );

            $resultAll = $this->dao->get();
			$count = count($resultAll->result());

            //View::newInstance()->_exportVariableToView( 'search_total_items', $count ) ;
            if($count >= $endLimit) {
	            $this->dao->select();
	            $this->dao->from($this->getTable_aam_log() );
	            $this->dao->orderBy('id', 'ASC');
	            $startLimit = $count - $endLimit;
	            $this->dao->limit(0, $startLimit);

	            $result = $this->dao->get();

	            if($result == NULL){
					return NULL;
				} else {
					return $result->result();
				}
			} else {
				return $count;
			}
        }

        /**
         * get Log entries
         *
         * @return array
         */
        public function getLogData($startLimit = 0, $logLimitP = 50)
        {
			$this->dao->select();
            $this->dao->from($this->getTable_aam_log() );

            $resultAll = $this->dao->get();
            if(empty($resultAll)) {
				$count = 0;
			} else {
				$count = count($resultAll->result());
			}

            View::newInstance()->_exportVariableToView( 'search_total_items', $count ) ;

            $this->dao->select();
            $this->dao->from($this->getTable_aam_log() );
            $this->dao->orderBy('id', 'DESC');
            $this->dao->limit($startLimit, $logLimitP);

            $result = $this->dao->get();

            if($result == NULL){
				return NULL;
			} else {
				return $result->result();
			}
        }

        /**
         * get re pub Item.
         *
         * @return array
         */
        public function getRepubItem($itemId, $secret)
        {
            $this->dao->query("SELECT * FROM " . DB_TABLE_PREFIX . "t_item WHERE pk_i_id = " . $itemId . " AND (s_secret = ". $secret . " OR fk_i_user_id = " . osc_logged_user_id() . ")");
            $this->dao->from(DB_TABLE_PREFIX . 't_item');

            $result = $this->dao->get();
            if($result == NULL){
				return NULL;
			} else {
				return $result->row();
			}
        }

        /**
         * get Distinct Items
         *
         * @return array
         */
        public function getDistinctItems()
        {
            $this->dao->select('DISTINCT *');
            $this->dao->from(DB_TABLE_PREFIX . 't_item_description');

            $result = $this->dao->get();
			return $result->result();
        }

        /**
         * get Items
         *
         * @return array
         */
        public function getAllItemsCron()
        {
            $this->dao->select();
            $this->dao->from(DB_TABLE_PREFIX . 't_item');

            $result = $this->dao->get();
			return $result->result();
        }

        /**
         * get plugin cat count
         *
         * @return array
         */
        public function getPluginCatCount($pluginName)
        {
            $this->dao->select('DISTINCT *');
            $this->dao->from(DB_TABLE_PREFIX . 't_plugin_category');
            $this->dao->where('s_plugin_name', $pluginName);

            $result = $this->dao->get();
			return count($result->result() );
        }

        /**
         * get Category expiration days
         *
         * @return array
         */
        public function getCatExp()
        {
            $this->dao->select();
            $this->dao->from(DB_TABLE_PREFIX . 't_category');

            $result = $this->dao->get();
			return $result->row();
        }

        /**
         * Return latest posted items, you can filter by category and specify the
         * number of items returned.
         *
         * @param int $numItems
         * @param mixed $category int or array(int)
         * @param bool $withPicture
         * @return array
         *
         * this is the same code that is in Search.php in the model folder. With a few mods
         * to bump republished ads back to the top.
         */
        public function getLatestAAMitems($numItems = 10, $category = array(), $withPicture = false)
        {
            $this->dao->select(DB_TABLE_PREFIX.'t_item.*  '); // , '.DB_TABLE_PREFIX.'t_item_location.*  , cd.s_name as s_category_name') ;
            // from + tables
            $this->dao->from( DB_TABLE_PREFIX.'t_item use index (PRIMARY)' ) ;
            /*$this->dao->join( DB_TABLE_PREFIX.'t_item_description',
                    DB_TABLE_PREFIX.'t_item_description.fk_i_item_id = '.DB_TABLE_PREFIX.'t_item.pk_i_id',
                    'LEFT' ) ;
            $this->dao->join( DB_TABLE_PREFIX.'t_item_location',
                    DB_TABLE_PREFIX.'t_item_location.fk_i_item_id = '.DB_TABLE_PREFIX.'t_item.pk_i_id',
                    'LEFT' ) ;
            $this->dao->join( DB_TABLE_PREFIX.'t_category',
                    DB_TABLE_PREFIX.'t_category.pk_i_id = '.DB_TABLE_PREFIX.'t_item.fk_i_category_id',
                    'LEFT' ) ;
            $this->dao->join( DB_TABLE_PREFIX.'t_category_description as cd',
                    DB_TABLE_PREFIX.'t_item.fk_i_category_id = cd.fk_i_category_id',
                    'LEFT' ) ;
            */
            if($withPicture) {
                $this->dao->from(sprintf('%st_item_resource', DB_TABLE_PREFIX));
                $this->dao->where(sprintf("%st_item_resource.s_content_type LIKE '%%image%%' AND %st_item.pk_i_id = %st_item_resource.fk_i_item_id", DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX));
            }
            $this->dao->from(sprintf('%st_item_adManage_limit', DB_TABLE_PREFIX));
            $this->dao->where(sprintf("%st_item.pk_i_id = %st_item_adManage_limit.fk_i_item_id", DB_TABLE_PREFIX, DB_TABLE_PREFIX));

            // where
            $whe  = DB_TABLE_PREFIX.'t_item.b_active = 1 AND ';
            $whe .= DB_TABLE_PREFIX.'t_item.b_enabled = 1 AND ';
            $whe .= DB_TABLE_PREFIX.'t_item.b_spam = 0 AND ';

            $whe .= '('.DB_TABLE_PREFIX.'t_item.b_premium = 1 || '.DB_TABLE_PREFIX.'t_item.dt_expiration >= \''. date('Y-m-d H:i:s').'\') ';

            //$whe .= 'AND '.DB_TABLE_PREFIX.'t_category.b_enabled = 1 ';
            if( is_array($category) && !empty ($category) ) {
                $listCategories = implode(',', $category );
                $whe .= ' AND '.DB_TABLE_PREFIX.'t_item.fk_i_category_id IN ('.$listCategories.') ';
            }
            $this->dao->where( $whe );

            // group by & order & limit
            $this->dao->groupBy(DB_TABLE_PREFIX.'t_item.dt_pub_date');
            $this->dao->orderBy(DB_TABLE_PREFIX.'t_item_adManage_limit.repub_date DESC, ' . DB_TABLE_PREFIX.'t_item.dt_pub_date', 'DESC');
            $this->dao->limit(0, $numItems);

            $rs = $this->dao->get();

            if($rs === false){
                return array();
            }
            if( $rs->numRows() == 0 ) {
                return array();
            }

            $items = $rs->result();
            return Item::newInstance()->extendData($items);
        }

		/**
		 *
		 * name: setExpireDaysAll
		 * @param int $days
		 * @return
		 * since 2.0
		 * date 10-11-12
		 *
		 */
        public function setExpireDaysAll($days)
        {
			$this->dao->select();
            $this->dao->from(DB_TABLE_PREFIX . 't_item');

            $result = $this->dao->get();
			$rows = $result->result();
			foreach($rows as $row){
				$this->dao->update($this->getTable_aam_limit(), array('delete_days'=> $days), array('fk_i_item_id' => $row['pk_i_id']))  ;
			}
		}

        /**
         * Insert into log
         *
         * @param string $error_text
         * @param int    $itemId
         */
        public function insertLog($error_text, $itemId = 000 )
        {
            $this->dao->insert($this->getTable_aam_log(), array('fk_i_item_id'  => $itemId , 'log_date' => date('m-d-Y H:i:s'), 'error_action' => $error_text)) ;
        }

        /**
         * Insert into limit table
         *
         * @param string $secret
         * @param int    $itemId, $r_times
         */
        public function insertNewLimit($itemId, $secret, $r_times = 0, $delDays = 0 )
        {
            $this->dao->insert($this->getTable_aam_limit(), array('fk_i_item_id'  => $itemId , 'r_secret' => $secret, 'r_times' => $r_times, 'repub_date' => date('Y-m-d H:i:s'), 'delete_days' => $delDays)) ;
        }

        /**
         * update category expiration days
         *
         * @param int $value, $id
         * @pram string $key
         */
        public function updateCatExp ($expDays, $id) {
			$this->dao->update(DB_TABLE_PREFIX . 't_category', array('i_expiration_days'=> $expDays), array('pk_i_id' => $id)) ;
			// update dt_expiration (tablel t_item) using category.i_expiration_days
			if($expDays > 0) {
				$update_dt_expiration = sprintf('update %st_item as a
					left join %st_category  as b on b.pk_i_id = a.fk_i_category_id
					set a.dt_expiration = date_add(NOW(), INTERVAL b.i_expiration_days DAY)
					where a.fk_i_category_id = %d ', DB_TABLE_PREFIX, DB_TABLE_PREFIX, $id );

				$this->dao->query($update_dt_expiration);
			// update dt_expiration (table t_item) using the max date value
			} else if( $expDays == 0) {
				$update_dt_expiration = sprintf("update %st_item as a
					set a.dt_expiration = '9999-12-31 23:59:59'
					where a.fk_i_category_id = %s", DB_TABLE_PREFIX, $id );

				$this->dao->query($update_dt_expiration);
			}
         }

        /**
         * update user limit table
         *
         * @param int $value, $id
         * @pram string $key
         */
        public function updateLimitUser($key, $value, $id)
        {
            $this->_update( $this->getTable_aam_limit(), array($key => $value), array('fk_i_item_id' => $id)) ;
        }

        /**
         * update items expiration date
         *
         * @param int $id
         * @pram string $expDate
         */
        public function updateExpireDate($expDate, $id)
        {
            $this->_update( DB_TABLE_PREFIX . 't_item', array('`dt_expiration`' => $expDate), array('pk_i_id' => $id) ) ;
        }

        /**
         * update the AAM limit table
         *
         * @param int $value, $id
         * @pram string $key
         */
        public function updateLimitRepub($rSecret, $rTimes, $id)
        {
            $this->_update( $this->getTable_aam_limit(), array('r_secret' => $rSecret, 'r_times' => $rTimes, 'ex_email' => 0, 'repub_date' => date('Y-m-d H:i:s'), 'delete_days' => osc_item_advanced_ad_management_deleteDays() ), array('fk_i_item_id' => $id)) ;
        }

        /**
         * update paypal publish table
         *
         * @param int $id
         *
         */
        public function updatePayPalPub($id)
        {
            $this->_update( DB_TABLE_PREFIX . 't_paypal_publish', array('dt_date' => date('Y-m-d H:i:s'), 'b_paid' => 0), array('fk_i_item_id' => $id) ) ;
        }

        /**
         * delete users item row from limit table
         *
         * @param int $value, $id
         * @pram string $key
         */
        public function deleteLimitUser($id)
        {
            $this->dao->delete( $this->getTable_aam_limit(), array('fk_i_item_id' => $id)) ;
        }

        /**
         * delete log data after inserted into txt log.
         *
         * @param int $value, $id
         * @pram string $key
         */
        public function deleteLogById($id)
        {
            $this->dao->delete( $this->getTable_aam_log(), array('id' => $id)) ;
        }

		/**
		 * Update dt_expiration field, using $i_expiration_days
		 * With a few changes so it updates the expiration date from todays date.
		 *
		 * @param type $i_expiration_days
		 * @return string new date expiration, false if error occurs
		 */
		function aam_updateExpirationDate($id, $i_expiration_days)
		{
			if($i_expiration_days > 0) {
				$sql =  sprintf("UPDATE %s SET dt_expiration = ' ", DB_TABLE_PREFIX . 't_item');
				$sql .= $i_expiration_days ;
				$sql .= sprintf("' WHERE pk_i_id = %d", $id);
			} else {
				$sql = sprintf("UPDATE %s SET dt_expiration = '9999-12-31 23:59:59'  WHERE pk_i_id = %d", DB_TABLE_PREFIX . 't_item', $id);
			}

			$result = $this->dao->query($sql);

			if($result && $result>0) {
				$this->dao->select('dt_expiration');
				$this->dao->from(DB_TABLE_PREFIX . 't_item');
				$this->dao->where('pk_i_id', (int)$id );
				$result = $this->dao->get();

				if($result && $result->result()>0) {
					$_item = $result->row();
					return $_item['dt_expiration'];
				}
				return false;
			}
			return false;
		}

		// update
        function _update($table, $values, $where)
        {
            $this->dao->from($table) ;
            $this->dao->set($values) ;
            $this->dao->where($where) ;
            return $this->dao->update() ;
        }
	}
?>
