FROM php:7.1-cli

# Configure the system
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
 	curl \
 	git \
	wget \
	zlib1g-dev

# Copy over the entry point and data which will be executed.
COPY .docker/entry-point.sh /usr/bin/entry-point
ADD . /usr/local/pktool

# Install a separate user and make sure this container is ran as the user.
RUN addgroup pktool \
 && adduser --no-create-home --disabled-password --ingroup pktool --gecos pktool pktool \
 && mkdir -p /data \
 && chown -R pktool:pktool /data \
 && chmod +x /usr/bin/entry-point

# Install the zip extension
RUN docker-php-ext-install zip \
 && docker-php-ext-enable zip

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php -r "if (hash_file('SHA384', 'composer-setup.php') === '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
 && php composer-setup.php \
 && php -r "unlink('composer-setup.php');" \
 && mv composer.phar /usr/local/bin/composer \
 && composer self-update \
 && chown pktool:pktool /usr/local/bin/composer \
 && composer install --no-dev -o

# The directory to work from.
WORKDIR /data

# The file to run
ENTRYPOINT ["/usr/bin/entry-point"]
