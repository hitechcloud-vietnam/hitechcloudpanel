package memory

import (
	"fmt"
	"os"
	"strconv"
	"strings"
)

type MemoryInfo struct {
	Total string
	Free  string
	Used  string
}

func GetMemoryInfo() MemoryInfo {
	var memoryInfo MemoryInfo
	// Read the contents of /proc/meminfo
	memInfo, err := os.ReadFile("/proc/meminfo")
	if err != nil {
		fmt.Println("Error reading /proc/meminfo:", err)
		return memoryInfo
	}

	//Declare Variables for Calculation
	var totalMem, availableMem int64

	// Parse the contents to get total, used, and free memory
	lines := strings.Split(string(memInfo), "\n")
	for _, line := range lines {
		fields := strings.Fields(line)
		if len(fields) < 2 {
			continue
		}
		key := strings.TrimRight(fields[0], ":")
		value := fields[1]
		switch key {
		case "MemTotal":
			totalMem, _ = strconv.ParseInt(value, 10, 64)
			memoryInfo.Total = value
		case "MemAvailable":
			availableMem, _ = strconv.ParseInt(value, 10, 64)
			memoryInfo.Free = value
		}
	}

	// Calculate the Used Memory
	usedMem := totalMem - availableMem
	memoryInfo.Used = strconv.FormatInt(usedMem, 10)

	return memoryInfo
}
