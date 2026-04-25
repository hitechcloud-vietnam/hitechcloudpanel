sudo gpasswd -d {{ $serverUser }} {{ $user }}
sudo userdel -r -f "{{ $user }}"
echo "User {{ $user }} has been deleted."
