package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
	"time"

	"github.com/vitodeploy/agent/pkg/config"
	"github.com/vitodeploy/agent/pkg/cpu"
	"github.com/vitodeploy/agent/pkg/disk"
	"github.com/vitodeploy/agent/pkg/memory"
)

type Payload struct {
	Load        float64 `json:"load"`
	DiskTotal   string  `json:"disk_total"`
	DiskFree    string  `json:"disk_free"`
	DiskUsed    string  `json:"disk_used"`
	MemoryTotal string  `json:"memory_total"`
	MemoryFree  string  `json:"memory_free"`
	MemoryUsed  string  `json:"memory_used"`
}

func main() {
	cfg := config.GetConfig()
	for {
		cpuInfo := cpu.GetCPUInfo()
		diskInfo := disk.GetDiskInfo()
		memoryInfo := memory.GetMemoryInfo()
		payload := Payload{
			Load:        cpuInfo.Load,
			DiskTotal:   diskInfo.Total,
			DiskFree:    diskInfo.Free,
			DiskUsed:    diskInfo.Used,
			MemoryTotal: memoryInfo.Total,
			MemoryFree:  memoryInfo.Free,
			MemoryUsed:  memoryInfo.Used,
		}
		jsonPayload, err := json.Marshal(payload)
		if err != nil {
			panic(err)
		}
		req, err := http.NewRequest("POST", cfg.Url, bytes.NewBuffer(jsonPayload))
		if err != nil {
			panic(err)
		}
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("Secret", cfg.Secret)
		client := &http.Client{}
		resp, err := client.Do(req)
		if err != nil {
			panic(err)
		}
		fmt.Println("Response Status:", resp.Status)
		resp.Body.Close()
		time.Sleep(time.Minute)
	}
}
