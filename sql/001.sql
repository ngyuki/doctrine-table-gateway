drop table if exists t_user;
drop table if exists t_post;

create table t_user(
    id int not null primary key auto_increment,
    name varchar(256),
    aa int,
    bb int,
    cc int
);

create table t_post(
    user_id int not null,
    post_id int not null,
    title varchar(256),
    primary key (user_id, post_id)
);
