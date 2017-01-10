DROP TABLE IF EXISTS tst_gbook;
CREATE TABLE `tst_gbook` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL COMMENT 'Имя пользователя',
	`email` varchar(255) NOT NULL COMMENT 'Адрес электронной почты',
	`subject` varchar(255) NOT NULL COMMENT 'Заголовок',
	`body` varchar(2000) NOT NULL COMMENT 'Текст сообщения',
	PRIMARY KEY (`id`) 
) ENGINE=InnoDB AUTO_INCREMENT=1 CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

INSERT INTO `tst_gbook`(`id`, `name`, `email`, `subject`, `body`) VALUES (1,'Сергей','serge@ya.ru','Заголовок сообщения', 'Текст сообщения');
INSERT INTO `tst_gbook`(`id`, `name`, `email`, `subject`, `body`) VALUES (2,'Андрей','andy@ya.ru','Заголовок сообщения andy', 'Текст сообщения andy');
INSERT INTO `tst_gbook`(`id`, `name`, `email`, `subject`, `body`) VALUES (3,'Виктор','victor@ya.ru','Заголовок сообщения victor', 'Текст сообщения victor');
