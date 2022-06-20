version: '3'
services:

    #PHP Service
    app:
        build:
            context: .
            dockerfile: Dockerfile
        image: digitalocean.com/php
        container_name: app
        restart: unless-stopped
        tty: true
        environment:
            SERVICE_NAME: app
            SERVICE_TAGS: dev
        working_dir: {! workingDir !}
        volumes:
            - ./:{! workingDir !}
            - ./php/local.ini:/usr/local/etc/php/conf.d/local.ini
        networks:
        - app-network

    #Nginx Service
    webserver:
        image: nginx:alpine
        container_name: webserver
        restart: unless-stopped
        tty: true
        ports:
            - "{! appExternalPort !}:80"
            - "443:443"
        volumes:
            - ./:{! workingDir !}
            - ./nginx/conf.d/:/etc/nginx/conf.d/
        networks:
            - app-network

    #MySQL Service
    db:
        image: mysql:5.7.22
        container_name: db
        restart: unless-stopped
        tty: true
        ports:
            - "{! mysqlExternalPort !}:3306"
        environment:
            MYSQL_DATABASE: {! mysqlDatabase !}
            MYSQL_ROOT_PASSWORD: {! mysqlRootPassword !}
            SERVICE_TAGS: dev
            SERVICE_NAME: mysql
        volumes:
            - dbdata:/var/lib/mysql
            - ./mysql/my.cnf:/etc/mysql/my.cnf
        networks:
            - app-network

#Docker Networks
networks:
    app-network:
        driver: bridge

#volumes
volumes:
    dbdata:
        driver: local

