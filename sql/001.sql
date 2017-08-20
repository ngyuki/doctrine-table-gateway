drop table if exists t_user;
create table t_user(
    id int not null primary key auto_increment,
    name varchar(256),
    aa int,
    bb int,
    cc int
);

insert into t_user values (1, 'id1', 0, 0, 0);
insert into t_user values (2, 'id2', 0, 0, 1);
insert into t_user values (3, 'id3', 0, 1, 0);
insert into t_user values (4, 'id4', 0, 1, 1);
insert into t_user values (5, 'id5', 1, 0, 0);
insert into t_user values (6, 'id6', 1, 0, 1);
insert into t_user values (7, 'id7', 1, 1, 0);
insert into t_user values (8, 'id8', 1, 1, 1);

drop table if exists t_post;
create table t_post(
    user_id int not null,
    post_id int not null,
    title varchar(256),
    primary key (user_id, post_id)
);

insert into t_post values (1, 1, 'post1');
insert into t_post values (1, 2, 'post2');
insert into t_post values (1, 3, 'post3');
