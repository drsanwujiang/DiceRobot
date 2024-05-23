#!/bin/bash

function warning() {
  printf "\033[33m%s\033[0m\n" "$1"
}

function error() {
  printf "\033[31m%s\033[0m\n" "$1"
  exit 1
}

# Check privilege
if [[ $EUID -ne 0 ]]; then
  error "Please run this script as root"
fi

host="0.0.0.0"
port="9500"

# Parse arguments
while [[ $# -gt 0 ]]; do
	case "$1" in
		--host)
			host="$2"
			shift
			;;
		--port)
			port="$2"
			shift
			;;
		--*)
			error "Illegal option $1"
			;;
	esac

	shift $(( $# > 0 ? 1 : 0 ))
done

# Check Python
if ! (python3 -V > /dev/null 2>&1); then
  error "Python not found"
fi

# Check Python version
if [[ $(python3 -c "import sys; print(sys.version_info[1])") -lt 10 ]]; then
  error "Python 3.10 or higher required"
fi

# Check pip
if ! (pip3 -V > /dev/null 2>&1); then
  warning "Python module pip not found"
  echo "Try to install pip"

  apt -qq update > /dev/null 2>&1

  if ! (apt -y -qq install python3-pip > /dev/null 2>&1); then
    error "Failed to install pip"
  fi
fi

# Check venv
if ! (python3 -m venv -h > /dev/null 2>&1); then
  warning "Python module venv not found"
  echo "Try to install venv"

  apt -qq update > /dev/null 2>&1

  if ! (apt -y -qq install python3-venv > /dev/null 2>&1); then
    error "Failed to install venv"
  fi
fi

# Install pipx and poetry
echo "Install pipx and poetry"

if ! (pip3 install --user pipx > /dev/null 2>&1); then
  apt -qq update > /dev/null 2>&1

  if ! (apt -y -qq install pipx > /dev/null 2>&1); then
    error "Failed to install pipx"
  fi
fi

python3 -m pipx ensurepath > /dev/null 2>&1
source "$HOME"/.bashrc

# Install poetry
if ! (pipx install poetry > /dev/null 2>&1); then
  error "Failed to install poetry"
fi

# Install dependencies
echo "Install dependencies"

if ! (poetry install > /dev/null 2>&1); then
  error "Failed to install dependencies"
fi

# Create service
echo "Create service"

cat > /etc/systemd/system/dicerobot.service <<EOF
[Unit]
Description=A TRPG game assistant
After=network.target

[Service]
Type=simple
WorkingDirectory=$(pwd)
ExecStart=/root/.local/bin/poetry run uvicorn app:dicerobot --host ${host} --port ${port} --log-level warning

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload > /dev/null 2>&1

echo "Success"