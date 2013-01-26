CREATE TABLE IF NOT EXISTS /*TABLE_PREFIX*/t_item_adManage_limit(
    fk_i_item_id int(10) unsigned NOT NULL,
    r_secret VARCHAR(10),
    r_times INT(10),
    ex_email INT(1) DEFAULT 0,
    ex_email_remind INT(1) DEFAULT 0,
    repub_date DATETIME,
    delete_days INT(11),


        PRIMARY KEY (fk_i_item_id),
        FOREIGN KEY (fk_i_item_id) REFERENCES /*TABLE_PREFIX*/t_item (pk_i_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';

CREATE TABLE IF NOT EXISTS /*TABLE_PREFIX*/t_item_adManage_log(
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    log_date VARCHAR(25),
    fk_i_item_id int(10),
    error_action VARCHAR(255),


        PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';
