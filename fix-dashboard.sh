#!/bin/bash
START=$(grep -n "return (" ./resources/js/inertia/Pages/Activity/Dashboard.tsx | cut -d: -f1)
sed -i "${START},\$d" ./resources/js/inertia/Pages/Activity/Dashboard.tsx
