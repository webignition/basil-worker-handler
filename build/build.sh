#!/usr/bin/env bash

docker build -t "smartassert/basil-worker-handler:${TAG_NAME:-master}" .
