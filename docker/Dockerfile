FROM php:7.4-fpm

ENV VOLUME_DIR /app
ENV VOLUME_USER app
ENV VOLUME_USER_ID 1001

# Each RUN statement executes on a different layer
RUN useradd -ms /bin/bash -u ${VOLUME_USER_ID} -U ${VOLUME_USER}

# Add composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# '&&' divides the commands
RUN apt-get update && apt-get install -y openssh-server mc \
    libzip-dev zip libxml2-dev nginx openssl \
    python-pip && pip install supervisor

RUN docker-php-ext-install bcmath opcache pcntl zip soap pdo_mysql intl && \
    pecl install xdebug-2.9.3 && \
    docker-php-ext-enable xdebug

### Copy source
COPY / ${VOLUME_DIR}/

RUN cp -R ${VOLUME_DIR}/build/nginx/* /etc/nginx && \
    # Supervisor
    mkdir -p /etc/supervisor && \
    cp -rf ${VOLUME_DIR}/build/supervisor/* /etc/supervisor && \
    # PHP
    cp -rf ${VOLUME_DIR}/build/php/php-fpm.conf /usr/local/etc/php-fpm.conf && \
    cp -rf ${VOLUME_DIR}/build/php/* /usr/local/etc/php

# SSH config
RUN echo 'root:c0mple][Pa$$' | chpasswd && \
    echo "${VOLUME_USER}:c0mple][Pa$$1" | chpasswd && \
    echo "www-data:c0mple][Pa$$2" | chpasswd
RUN sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config && \
    echo "AllowUsers ${VOLUME_USER} root www-data" >> /etc/ssh/sshd_config && \
    sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd && \
    echo "export VISIBLE=now" >> /etc/profile

RUN mkdir -p /var/log/supervisor && \
    mkdir -p /var/log/php-fpm && \
    mkdir -p /var/log/nginx && \
    mkdir -p /var/run/php-fpm && \
    mkdir -p /var/run/sshd && \
    chown -R ${VOLUME_USER}:${VOLUME_USER} ${VOLUME_DIR} && \
    chown -R ${VOLUME_USER}:${VOLUME_USER} /var/log/php-fpm && \
    chown -R ${VOLUME_USER}:${VOLUME_USER} /var/run/php-fpm && \
    chown -R ${VOLUME_USER}:${VOLUME_USER} /var/log/nginx

RUN usermod -u 1000 ${VOLUME_USER} && \
    touch ${VOLUME_DIR}/var && \
    chmod -R 775 ${VOLUME_DIR}/var && \
    usermod -a -G ${VOLUME_USER} www-data

WORKDIR ${VOLUME_DIR}

CMD ["/usr/local/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]