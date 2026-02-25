package handler

import (
	"fmt"
	"net/http"
)

func Handler(w http.ResponseWriter, r *http.Request) {
	fmt.Fprintf(w, "<h1>SVG Art API (Go)</h1><p>Ready to serve artistic data.</p>")
}
