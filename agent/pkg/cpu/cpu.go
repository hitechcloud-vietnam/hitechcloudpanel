package cpu

import (
	"fmt"
	"os"
	"strconv"
	"strings"
)

type CPUInfo struct {
	Load float64
}

func GetCPUInfo() CPUInfo {
	load, err := loadAvarage()
	if err != nil {
		fmt.Println("Error:", err)
	}
	return CPUInfo{
		Load: load,
	}
}

func loadAvarage() (float64, error) {
	// Read the contents of /proc/loadavg
	data, err := os.ReadFile("/proc/loadavg")
	if err != nil {
		return 0, err
	}

	// Parse the contents
	loadavg := strings.Fields(string(data))

	// string to float64
	load, err := strconv.ParseFloat(loadavg[0], 64)
	if err != nil {
		return 0, err
	}

	return load, nil
}
