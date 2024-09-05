#!/bin/bash

function success() {
  printf "\033[32m%s\033[0m\n" "$1"
}

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

  apt-get -qq update > /dev/null 2>&1

  if ! (apt-get -y -qq install python3-pip > /dev/null 2>&1); then
    error "Failed to install pip"
  fi
fi

# Check venv
if ! (python3 -m venv -h > /dev/null 2>&1); then
  warning "Python module venv not found"
  echo "Install venv"

  apt-get -qq update > /dev/null 2>&1

  if ! (apt-get -y -qq install python3-venv > /dev/null 2>&1); then
    error "Failed to install venv"
  fi
fi

# Install pipx
echo "Install pipx"

if ! (pipx --version > /dev/null 2>&1); then
  if ! (pip3 install --user --index-url https://pypi.tuna.tsinghua.edu.cn/simple pipx > /dev/null 2>&1); then
    apt-get -qq update > /dev/null 2>&1

    if ! (apt-get -y -qq install pipx > /dev/null 2>&1); then
      error "Failed to install pipx"
    fi
  fi

  python3 -m pipx ensurepath > /dev/null 2>&1
  source "$HOME"/.bashrc
fi

# Install Poetry
echo "Install Poetry"

if ! (poetry --version > /dev/null 2>&1); then
  if ! (pipx install --index-url https://pypi.tuna.tsinghua.edu.cn/simple poetry > /dev/null 2>&1); then
    error "Failed to install Poetry"
  fi

  source "$HOME"/.bashrc
fi

# Install dependencies
echo "Install dependencies"

if ! (apt-get -y -qq install libnss3 libgbm1 libasound2 > /dev/null 2>&1); then
  error "Failed to install dependencies"
fi

if ! (poetry install > /dev/null 2>&1); then
  error "Failed to install dependencies"
fi

# Create service
echo "Create service"

cat > /etc/systemd/system/dicerobot.service <<EOF
[Unit]
Description=A TRPG assistant bot
After=network.target

[Service]
Type=simple
WorkingDirectory=$(pwd)
ExecStart=/root/.local/bin/poetry run uvicorn app:dicerobot --host ${host} --port ${port} --log-level warning

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload > /dev/null 2>&1
systemctl enable dicerobot
systemctl start dicerobot

success "Success"