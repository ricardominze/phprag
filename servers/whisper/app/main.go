package main

import (
	"fmt"
	"io"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
)

const (
	audioDir = "/app/audio"
	modelDir = "/app/models"
	model    = "ggml-base.bin"
)

func transcribeHandler(w http.ResponseWriter, r *http.Request) {

	r.ParseMultipartForm(32 << 20)

	file, header, err := r.FormFile("file")
	if err != nil {
		http.Error(w, "Falha ao ler arquivo: "+err.Error(), http.StatusBadRequest)
		return
	}
	defer file.Close()

	// Salva áudio original
	originalPath := filepath.Join(audioDir, header.Filename)
	outFile, err := os.Create(originalPath)
	if err != nil {
		http.Error(w, "Erro ao salvar áudio: "+err.Error(), http.StatusInternalServerError)
		return
	}
	defer outFile.Close()
	io.Copy(outFile, file)

	// Gera nome base sem extensão
	filenameOnly := filepath.Base(header.Filename)
	base := strings.TrimSuffix(filenameOnly, filepath.Ext(filenameOnly))
	wavPath := filepath.Join(audioDir, base+".wav")
	outPrefix := filepath.Join(audioDir, base)

	// Converte para .wav se não estiver no formato esperado
	if strings.ToLower(filepath.Ext(header.Filename)) != ".wav" {
		conv := exec.Command("ffmpeg", "-y", "-i", originalPath, "-ar", "16000", "-ac", "1", wavPath)
		if out, err := conv.CombinedOutput(); err != nil {
			http.Error(w, "Erro ao converter com ffmpeg:"+string(out), http.StatusInternalServerError)
			return
		}
	} else {
		wavPath = originalPath
	}

	// Whisper
	cmd := exec.Command("/app/whisper/build/bin/whisper-cli",
		"-m", filepath.Join(modelDir, model),
		"-f", wavPath,
		"-otxt",
		"-of", outPrefix,
		"-l", "pt",
	)

	output, err := cmd.CombinedOutput()

	if err != nil {
		http.Error(w, "Erro ao rodar whisper:"+string(output), http.StatusInternalServerError)
		return
	}

	// Lê resultado
	txtFile := outPrefix + ".txt"
	data, err := os.ReadFile(txtFile)
	if err != nil {
		http.Error(w, "Erro ao ler resultado: "+err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "text/plain")
	w.Write(data)
}

func main() {

	port := "8000"

	os.MkdirAll(audioDir, 0755)
	http.HandleFunc("/transcribe", transcribeHandler)
	fmt.Println("Servidor iniciado em http://0.0.0.0:" + port)
	http.ListenAndServe(":"+port, nil)
}
