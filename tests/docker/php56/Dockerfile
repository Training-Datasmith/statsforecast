FROM php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143

COPY --from=debian@sha256:734a72b3d6142a71993b4bc580ca01d8ca687f77a2c422d96a961d3376b179bc /etc/apt/trusted.gpg.d/debian-archive-bullseye-stable.asc /etc/apt/trusted.gpg.d/debian-archive-bullseye-stable.asc
COPY --from=debian@sha256:734a72b3d6142a71993b4bc580ca01d8ca687f77a2c422d96a961d3376b179bc /etc/apt/trusted.gpg.d/debian-archive-bullseye-security-automatic.asc /etc/apt/trusted.gpg.d/debian-archive-bullseye-security-automatic.asc

RUN docker-php-ext-install mysqli
