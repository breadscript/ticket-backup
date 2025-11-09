CREATE TABLE `redbel`.`sys_moduletb`
( `id` INT NOT NULL AUTO_INCREMENT ,
`modulecategoryid` INT NULL DEFAULT NULL ,
`modulename` VARCHAR(255) NULL DEFAULT NULL ,
`statid` INT NOT NULL DEFAULT '1' ,
`modulepath` VARCHAR(255) NULL DEFAULT NULL ,
`moduleorderno` INT NULL DEFAULT NULL ,
`datecreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ,
`createdby_userid` INT NOT NULL ,
`modulepath_index` VARCHAR(255) NOT NULL ,
`modulepath_module` VARCHAR(255) NOT NULL ,
`secondlevel` INT NOT NULL DEFAULT '0' ,
`firstlevel` INT NOT NULL DEFAULT '0' ,
`usergroupmasterid` INT NOT NULL ,
PRIMARY KEY (`id`))
ENGINE = InnoDB CHARSET=utf8 COLLATE utf8_general_ci;



INSERT INTO `sys_modulecategorytb` (`id`, `modulecategory`, `statid`, `modulecategoryorderno`,
`datecreated`, `createdby_userid`, `modulecategorylogo`) VALUES (NULL, 'Client Settings', '1', '2', CURRENT_TIMESTAMP, '1', 'menu-icon fa fa-cogs');