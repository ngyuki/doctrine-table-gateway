drop table if exists example;
create table example(
    id int not null primary key auto_increment,
    name varchar(256),
    a int,
    b int,
    c int,
    d int,
    `col` varchar(256),
    `key` varchar(256),
    `val` varchar(256)
);

insert into example values (1, 'abc', 1, 2, 3, 4, 'col', 'key', 'val');
