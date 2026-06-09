create DATABASE Sistema_agendamiento;

create table Clientes (
id_clientes int identity (1,1) primary key,
Nombre varchar (30) not null,
Cedula varchar (20) not null unique,
Apellidos varchar (30) not null,
Celular varchar (20) not null,
correo varchar (100) not null,
Fecha date not null,
hora time not null,

);


create table servicios (
id_servicios int identity (1,1) primary key,
tipo_de_servicio varchar (50), 
precio decimal (10,2) not null,
);


create table citas (
Id_citas int identity (1,1) primary key,
id_cliente varchar (30) not null,
fecha_cita date not null,
);