FROM golang:1.21

RUN apt-get update && apt-get install -y cmake ffmpeg git build-essential

# Clona e compila whisper.cpp corretamente
RUN git clone https://github.com/ggerganov/whisper.cpp.git /app/whisper
WORKDIR /app/whisper
RUN make

WORKDIR /app
COPY app /app/app
WORKDIR /app/app

RUN go mod tidy && go build -o /app/server

WORKDIR /app
RUN mkdir -p /app/models /app/audio

EXPOSE 8000
CMD ["/app/server"]