echo "load:$(uptime | awk -F'load average:' '{print $2}' | awk -F, '{print $1}' | tr -d ' ')"
CPU_SAMPLE_1=$(awk '/^cpu / {print $2+$3+$4":"$2+$3+$4+$5+$6+$7+$8}' /proc/stat)
sleep 1
CPU_SAMPLE_2=$(awk '/^cpu / {print $2+$3+$4":"$2+$3+$4+$5+$6+$7+$8}' /proc/stat)
echo "$CPU_SAMPLE_1 $CPU_SAMPLE_2" | awk '
{
	split($1, cpu1, ":");
	split($2, cpu2, ":");
	totalDelta = cpu2[2] - cpu1[2];
	usage = 0;
	if (totalDelta > 0) {
		usage = ((cpu2[1] - cpu1[1]) / totalDelta) * 100;
	}

	printf "cpu_usage:%.2f\n", usage;
}'
echo "cpu_cores:$(nproc)"
echo "memory_total:$(free -k | awk 'NR==2{print $2}')"
echo "memory_used:$(free -k | awk 'NR==2{print $3}')"
echo "memory_free:$(free -k | awk 'NR==2{print $7}')"
echo "disk_total:$(df -BM / | awk 'NR==2{print $2}' | sed 's/M//')"
echo "disk_used:$(df -BM / | awk 'NR==2{print $3}' | sed 's/M//')"
echo "disk_free:$(df -BM / | awk 'NR==2{print $4}' | sed 's/M//')"
NET_SAMPLE_1=$(awk -F'[: ]+' '$1 !~ /^(lo|Inter|face)$/ && NF > 9 {rx += $3; tx += $11} END {print rx":"tx}' /proc/net/dev)
DISK_SAMPLE_1=$(awk '$3!="loop" && $3!="ram" {read+=$6; write+=$10; io+=$4+$8} END {print read":"write":"io}' /proc/diskstats)
IOWAIT_SAMPLE_1=$(awk '/^cpu / {print $6":"$2+$3+$4+$5+$6+$7+$8}' /proc/stat)
sleep 1
NET_SAMPLE_2=$(awk -F'[: ]+' '$1 !~ /^(lo|Inter|face)$/ && NF > 9 {rx += $3; tx += $11} END {print rx":"tx}' /proc/net/dev)
DISK_SAMPLE_2=$(awk '$3!="loop" && $3!="ram" {read+=$6; write+=$10; io+=$4+$8} END {print read":"write":"io}' /proc/diskstats)
IOWAIT_SAMPLE_2=$(awk '/^cpu / {print $6":"$2+$3+$4+$5+$6+$7+$8}' /proc/stat)
echo "$NET_SAMPLE_1 $NET_SAMPLE_2 $DISK_SAMPLE_1 $DISK_SAMPLE_2 $IOWAIT_SAMPLE_1 $IOWAIT_SAMPLE_2" | awk '
{
	split($1, net1, ":");
	split($2, net2, ":");
	split($3, disk1, ":");
	split($4, disk2, ":");
	split($5, io1, ":");
	split($6, io2, ":");

	up = net2[2] - net1[2];
	down = net2[1] - net1[1];
	totalSent = net2[2];
	totalReceived = net2[1];

	readSectors = disk2[1] - disk1[1];
	writeSectors = disk2[2] - disk1[2];
	ioOps = disk2[3] - disk1[3];
	readBytes = readSectors * 512;
	writeBytes = writeSectors * 512;

	ioWait = 0;
	totalDelta = io2[2] - io1[2];
	if (totalDelta > 0) {
		ioWait = ((io2[1] - io1[1]) / totalDelta) * 100;
	}

	printf "network_upstream:%.2f\n", up;
	printf "network_downstream:%.2f\n", down;
	printf "network_total_sent:%.2f\n", totalSent;
	printf "network_total_received:%.2f\n", totalReceived;
	printf "disk_read:%.2f\n", readBytes;
	printf "disk_write:%.2f\n", writeBytes;
	printf "disk_tps:%.2f\n", ioOps;
	printf "io_wait:%.2f\n", ioWait;
}'
