package disk

import (
	"fmt"
	"os/exec"
	"strings"
)

type DiskInfo struct {
	Total string
	Free  string
	Used  string
}

func GetDiskInfo() DiskInfo {
	// Get disk usage of the current working directory
	info, err := diskUsage("/")
	if err != nil {
		fmt.Println("Error:", err)
		panic(err)
	}

	return info
}

// DiskUsage returns the disk usage of the path in bytes
func diskUsage(path string) (usage DiskInfo, err error) {
	// Run the df command to get disk information
	cmd := exec.Command("df", "-BM", path) // Use -BM option to get sizes in megabytes
	output, err := cmd.Output()
	if err != nil {
		fmt.Println("Error running df command:", err)
		return
	}

	// Parse the output to get disk total, usage, and free
	lines := strings.Split(string(output), "\n")
	if len(lines) < 2 {
		fmt.Println("Unexpected output from df command")
		return
	}

	fields := strings.Fields(lines[1])
	if len(fields) < 4 {
		fmt.Println("Unexpected output format")
		return
	}

	usage.Total = strings.Replace(fields[1], "M", "", 1)
	usage.Used = strings.Replace(fields[2], "M", "", 1)
	usage.Free = strings.Replace(fields[3], "M", "", 1)
	return
}
