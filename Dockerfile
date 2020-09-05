FROM ubuntu:trusty

ENV DEBIAN_FRONTEND noninteractive
ENV PATH $PATH:/usr/local/nginx/sbin

EXPOSE 1935
EXPOSE 80
EXPOSE 443

# create directories
RUN mkdir /src /config /logs /data /data/hls /data/recorders

# update and upgrade packages
RUN apt-get update && \
  apt-get upgrade -y && \
  apt-get clean && \
  apt-get install -y --no-install-recommends build-essential \
  wget software-properties-common && \
# ffmpeg
  add-apt-repository ppa:mc3man/trusty-media && \
  apt-get update && \
  apt-get install -y --no-install-recommends ffmpeg && \
# nginx dependencies
  apt-get install -y --no-install-recommends libpcre3-dev \
  zlib1g-dev libssl-dev wget && \
  rm -rf /var/lib/apt/lists/* 
# php
RUN add-apt-repository ppa:ondrej/php && \
  apt-get update && \
  apt-get install -y --no-install-recommends php5-fpm php5-cli php5-curl && service php5-fpm start

# get nginx source
RUN mkdir -p /src
WORKDIR /src
RUN wget http://nginx.org/download/nginx-1.7.5.tar.gz && \
  tar zxf nginx-1.7.5.tar.gz && \
  rm nginx-1.7.5.tar.gz && \
# get nginx-rtmp module
  wget https://github.com/arut/nginx-rtmp-module/archive/v1.1.6.tar.gz && \
  tar zxf v1.1.6.tar.gz && \
  rm v1.1.6.tar.gz

# compile nginx
WORKDIR /src/nginx-1.7.5
RUN ./configure --with-http_ssl_module --add-module=/src/nginx-rtmp-module-1.1.6 \
  --conf-path=/config/nginx.conf \
  --error-log-path=/logs/error.log \
  --http-log-path=/logs/access.log && \
  make && \
  make install

ADD nginx.conf /config/nginx.conf
#ADD static /static

# RUN sed -i -e "s/{webSiteRootURL}/${webSiteRootURL}/g" /static/*.php

# RUN sed -i -e "s/variables_order = \"GPCS\"/variables_order = \"GPCSE\"/g" /etc/php5/fpm/php.ini

RUN chmod -R 777 /data

WORKDIR /
RUN echo "exit 0" > /usr/sbin/policy-rc.d
CMD /etc/init.d/php5-fpm restart && nginx