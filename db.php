<?php
class database {
	
		public function __construct()
		{
			$this->db = new PDO('mysql:host='.DB_HOST.'; dbname='.DB_NAME.'; charset=utf8mb4', DB_LOGIN, DB_PWD);
			return $this->db;
		}

		public function query($sql, $params = [])
		{
			$stmt = $this->db->prepare($sql);
			if ( !empty($params) ) {
				foreach ($params as $key => $value) {
					$stmt->bindValue(":$key", $value);
				}
			}
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		public function query_multi_params($sql, $params = [])
		{
			$stmt = $this->db->prepare($sql);
			if ( !empty($params) ) {
				foreach ($params as $value) {
					$stmt->bindValue("?", $value);
				}
			}
			$stmt->execute($params);
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		public function last_id() {
			return $this->db->lastInsertId();
		}
		
}
