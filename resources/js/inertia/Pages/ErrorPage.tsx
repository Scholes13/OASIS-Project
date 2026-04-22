import { Head, router } from '@inertiajs/react';
import { Home, ArrowLeft, RefreshCw, LogIn } from 'lucide-react';
import type { ReactNode } from 'react';

interface ErrorPageProps {
    status: number;
    message?: string;
}

/* ═══════════════════════════════════════════════════════════════════════════
   ARTISTIC SVG ILLUSTRATIONS
   Scene-based illustrations with characters, objects, depth, and animation.
   Style: flat illustration with soft gradients, inspired by unDraw/Storyset.
   Brand color: Oasis blue (#2563EB)
   ═══════════════════════════════════════════════════════════════════════════ */

/** 404 — Person looking at a broken map / lost in space */
function Illustration404() {
    return (
        <svg viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full max-w-[360px]">
            <defs>
                <linearGradient id="sky404" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#EFF6FF" />
                    <stop offset="100%" stopColor="#DBEAFE" />
                </linearGradient>
                <linearGradient id="ground404" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#BFDBFE" stopOpacity="0.3" />
                    <stop offset="100%" stopColor="#93C5FD" stopOpacity="0.1" />
                </linearGradient>
            </defs>

            {/* Background */}
            <rect width="400" height="300" fill="url(#sky404)" />

            {/* Ground / floor */}
            <ellipse cx="200" cy="260" rx="170" ry="20" fill="url(#ground404)" />

            {/* Large "404" text in background */}
            <text x="200" y="160" textAnchor="middle" fontSize="120" fontWeight="900" fill="#2563EB" opacity="0.06" fontFamily="system-ui, sans-serif">404</text>

            {/* Broken signpost */}
            <rect x="260" y="130" width="6" height="120" rx="3" fill="#94A3B8" />
            <rect x="240" y="125" width="50" height="28" rx="6" fill="#E2E8F0" stroke="#CBD5E1" strokeWidth="1.5" />
            <line x1="252" y1="135" x2="278" y2="135" stroke="#94A3B8" strokeWidth="2" strokeLinecap="round" />
            <line x1="252" y1="143" x2="270" y2="143" stroke="#CBD5E1" strokeWidth="2" strokeLinecap="round" />
            {/* Broken arrow on sign */}
            <path d="M275 139 L282 135 L275 131" stroke="#94A3B8" strokeWidth="1.5" fill="none" strokeLinecap="round" />
            {/* Crack in sign */}
            <path d="M258 125 L262 138 L256 148 L260 153" stroke="#CBD5E1" strokeWidth="1" fill="none" />

            {/* Person standing confused */}
            {/* Body */}
            <ellipse cx="170" cy="248" rx="18" ry="5" fill="#1E40AF" opacity="0.1" />
            {/* Legs */}
            <path d="M162 220 L158 248" stroke="#1E3A5F" strokeWidth="3.5" strokeLinecap="round" />
            <path d="M178 220 L182 248" stroke="#1E3A5F" strokeWidth="3.5" strokeLinecap="round" />
            {/* Shoes */}
            <ellipse cx="156" cy="250" rx="7" ry="3" fill="#1E3A5F" />
            <ellipse cx="184" cy="250" rx="7" ry="3" fill="#1E3A5F" />
            {/* Torso */}
            <path d="M155 185 C155 200 160 220 170 222 C180 220 185 200 185 185 Z" fill="#2563EB" />
            {/* Arms */}
            <path d="M155 192 L135 205 L130 195" stroke="#2563EB" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" fill="none" />
            <path d="M185 192 L200 180" stroke="#2563EB" strokeWidth="3.5" strokeLinecap="round" fill="none" />
            {/* Hand scratching head */}
            <circle cx="202" cy="178" r="4" fill="#FBBF7B" />
            {/* Head */}
            <circle cx="170" cy="170" r="18" fill="#FCD9B6" />
            {/* Hair */}
            <path d="M153 165 C153 150 162 145 170 145 C178 145 187 150 187 165" fill="#1E3A5F" />
            <path d="M153 165 C155 162 160 160 165 163" fill="#1E3A5F" />
            {/* Eyes — confused look */}
            <circle cx="164" cy="172" r="2" fill="#1E3A5F" />
            <circle cx="176" cy="172" r="2" fill="#1E3A5F" />
            {/* Eyebrows — raised */}
            <path d="M161 167 Q164 164 167 167" stroke="#1E3A5F" strokeWidth="1.2" fill="none" />
            <path d="M173 167 Q176 164 179 167" stroke="#1E3A5F" strokeWidth="1.2" fill="none" />
            {/* Mouth — confused */}
            <path d="M166 180 Q170 178 174 180" stroke="#C4846C" strokeWidth="1.2" fill="none" />

            {/* Map in left hand */}
            <rect x="118" y="188" width="18" height="14" rx="2" fill="#FEF3C7" stroke="#FCD34D" strokeWidth="1" transform="rotate(-15 127 195)" />
            <line x1="122" y1="193" x2="132" y2="191" stroke="#FBBF24" strokeWidth="0.8" />
            <line x1="123" y1="197" x2="130" y2="195" stroke="#FDE68A" strokeWidth="0.8" />

            {/* Question marks floating */}
            <text x="210" y="165" fontSize="16" fill="#2563EB" opacity="0.5" fontFamily="system-ui" fontWeight="700">?</text>
            <text x="225" y="155" fontSize="12" fill="#60A5FA" opacity="0.4" fontFamily="system-ui" fontWeight="700">?
                <animate attributeName="y" values="155;148;155" dur="3s" repeatCount="indefinite" />
            </text>
            <text x="195" y="150" fontSize="10" fill="#93C5FD" opacity="0.3" fontFamily="system-ui" fontWeight="700">?
                <animate attributeName="y" values="150;143;150" dur="2.5s" repeatCount="indefinite" />
            </text>

            {/* Decorative clouds */}
            <ellipse cx="60" cy="50" rx="30" ry="12" fill="white" opacity="0.7" />
            <ellipse cx="50" cy="48" rx="18" ry="10" fill="white" opacity="0.7" />
            <ellipse cx="330" cy="70" rx="25" ry="10" fill="white" opacity="0.5" />
            <ellipse cx="340" cy="68" rx="15" ry="8" fill="white" opacity="0.5" />

            {/* Small plant near signpost */}
            <path d="M275 250 Q278 240 282 245" stroke="#86EFAC" strokeWidth="2" fill="none" />
            <path d="M278 250 Q275 238 270 242" stroke="#86EFAC" strokeWidth="2" fill="none" />
            <rect x="276" y="250" width="4" height="6" rx="1" fill="#A78BFA" opacity="0.3" />
        </svg>
    );
}

/** 403 — Person blocked by a large locked gate */
function Illustration403() {
    return (
        <svg viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full max-w-[360px]">
            <defs>
                <linearGradient id="sky403" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#FFF7ED" />
                    <stop offset="100%" stopColor="#FFEDD5" />
                </linearGradient>
            </defs>

            <rect width="400" height="300" fill="url(#sky403)" />

            {/* Ground */}
            <ellipse cx="200" cy="265" rx="180" ry="18" fill="#FED7AA" opacity="0.3" />

            {/* Large "403" in background */}
            <text x="200" y="160" textAnchor="middle" fontSize="120" fontWeight="900" fill="#EA580C" opacity="0.05" fontFamily="system-ui, sans-serif">403</text>

            {/* Large gate / wall */}
            <rect x="155" y="80" width="90" height="180" rx="4" fill="#FDE8D0" stroke="#FDBA74" strokeWidth="2" />
            {/* Gate arch */}
            <path d="M165 80 L165 130 Q200 100 235 130 L235 80" fill="#FFF7ED" stroke="#FDBA74" strokeWidth="2" />
            {/* Gate bars */}
            <line x1="180" y1="130" x2="180" y2="260" stroke="#FB923C" strokeWidth="2.5" opacity="0.4" />
            <line x1="200" y1="115" x2="200" y2="260" stroke="#FB923C" strokeWidth="2.5" opacity="0.4" />
            <line x1="220" y1="130" x2="220" y2="260" stroke="#FB923C" strokeWidth="2.5" opacity="0.4" />
            {/* Horizontal bars */}
            <line x1="165" y1="170" x2="235" y2="170" stroke="#FB923C" strokeWidth="2" opacity="0.3" />
            <line x1="165" y1="210" x2="235" y2="210" stroke="#FB923C" strokeWidth="2" opacity="0.3" />

            {/* Large lock on gate */}
            <rect x="188" y="178" width="24" height="20" rx="5" fill="#EA580C" opacity="0.8" />
            <path d="M194 178 L194 170 C194 164 198 160 200 160 C202 160 206 164 206 170 L206 178" stroke="#EA580C" strokeWidth="2.5" fill="none" />
            <circle cx="200" cy="189" r="3" fill="#FFF7ED" />
            <line x1="200" y1="192" x2="200" y2="196" stroke="#FFF7ED" strokeWidth="1.5" />

            {/* Person standing in front of gate, looking up */}
            <ellipse cx="100" cy="253" rx="16" ry="4" fill="#9A3412" opacity="0.1" />
            {/* Legs */}
            <path d="M93 228 L90 253" stroke="#292524" strokeWidth="3" strokeLinecap="round" />
            <path d="M107 228 L110 253" stroke="#292524" strokeWidth="3" strokeLinecap="round" />
            <ellipse cx="88" cy="255" rx="6" ry="2.5" fill="#292524" />
            <ellipse cx="112" cy="255" rx="6" ry="2.5" fill="#292524" />
            {/* Torso */}
            <path d="M87 195 C87 208 92 228 100 230 C108 228 113 208 113 195 Z" fill="#EA580C" />
            {/* Arms — one hand on hip, one reaching toward gate */}
            <path d="M87 200 L78 215 L85 220" stroke="#EA580C" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" fill="none" />
            <path d="M113 200 L135 195 L148 190" stroke="#EA580C" strokeWidth="3" strokeLinecap="round" fill="none" />
            <circle cx="150" cy="189" r="3.5" fill="#FBBF7B" />
            {/* Head — looking up at lock */}
            <circle cx="100" cy="180" r="16" fill="#FCD9B6" />
            <path d="M85 175 C85 162 92 157 100 157 C108 157 115 162 115 175" fill="#292524" />
            {/* Eyes looking up */}
            <circle cx="95" cy="178" r="1.8" fill="#292524" />
            <circle cx="105" cy="178" r="1.8" fill="#292524" />
            <circle cx="95.5" cy="177" r="0.6" fill="white" />
            <circle cx="105.5" cy="177" r="0.6" fill="white" />
            {/* Mouth — slight frown */}
            <path d="M96 186 Q100 184 104 186" stroke="#C4846C" strokeWidth="1" fill="none" />

            {/* "STOP" / "NO ENTRY" sign */}
            <circle cx="310" cy="120" r="22" fill="#FEE2E2" stroke="#FCA5A5" strokeWidth="2" />
            <line x1="296" y1="120" x2="324" y2="120" stroke="#EF4444" strokeWidth="4" strokeLinecap="round" />

            {/* Decorative elements */}
            <circle cx="50" cy="80" r="3" fill="#FDBA74" opacity="0.4">
                <animate attributeName="cy" values="80;74;80" dur="3s" repeatCount="indefinite" />
            </circle>
            <circle cx="350" cy="200" r="4" fill="#FED7AA" opacity="0.3">
                <animate attributeName="cy" values="200;194;200" dur="2.5s" repeatCount="indefinite" />
            </circle>
            <circle cx="320" cy="50" r="2.5" fill="#FDBA74" opacity="0.3">
                <animate attributeName="cy" values="50;44;50" dur="3.5s" repeatCount="indefinite" />
            </circle>

            {/* Small bush */}
            <ellipse cx="300" cy="258" rx="15" ry="8" fill="#86EFAC" opacity="0.3" />
            <ellipse cx="310" cy="255" rx="10" ry="7" fill="#86EFAC" opacity="0.4" />
        </svg>
    );
}

/** 500 — Broken robot / machine with sparks */
function Illustration500() {
    return (
        <svg viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full max-w-[360px]">
            <defs>
                <linearGradient id="sky500" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#FEF2F2" />
                    <stop offset="100%" stopColor="#FEE2E2" />
                </linearGradient>
            </defs>

            <rect width="400" height="300" fill="url(#sky500)" />
            <ellipse cx="200" cy="262" rx="170" ry="18" fill="#FECACA" opacity="0.2" />

            <text x="200" y="160" textAnchor="middle" fontSize="120" fontWeight="900" fill="#EF4444" opacity="0.05" fontFamily="system-ui, sans-serif">500</text>

            {/* Robot body */}
            <rect x="160" y="120" width="80" height="100" rx="12" fill="#F1F5F9" stroke="#CBD5E1" strokeWidth="2" />
            {/* Robot chest panel */}
            <rect x="175" y="145" width="50" height="30" rx="6" fill="#E2E8F0" stroke="#94A3B8" strokeWidth="1" />
            {/* Blinking lights on chest */}
            <circle cx="188" cy="155" r="4" fill="#EF4444">
                <animate attributeName="opacity" values="1;0.2;1" dur="1s" repeatCount="indefinite" />
            </circle>
            <circle cx="200" cy="155" r="4" fill="#F59E0B">
                <animate attributeName="opacity" values="0.2;1;0.2" dur="1.5s" repeatCount="indefinite" />
            </circle>
            <circle cx="212" cy="155" r="4" fill="#EF4444">
                <animate attributeName="opacity" values="1;0.3;1" dur="0.8s" repeatCount="indefinite" />
            </circle>
            {/* Belly bolt */}
            <circle cx="200" cy="195" r="6" fill="#CBD5E1" stroke="#94A3B8" strokeWidth="1" />
            <line x1="197" y1="195" x2="203" y2="195" stroke="#94A3B8" strokeWidth="1.5" />
            <line x1="200" y1="192" x2="200" y2="198" stroke="#94A3B8" strokeWidth="1.5" />

            {/* Robot head — tilted */}
            <g transform="rotate(-8 200 95)">
                <rect x="172" y="72" width="56" height="48" rx="10" fill="#F1F5F9" stroke="#CBD5E1" strokeWidth="2" />
                {/* Antenna */}
                <line x1="200" y1="72" x2="200" y2="58" stroke="#94A3B8" strokeWidth="2" />
                <circle cx="200" cy="55" r="5" fill="#EF4444" opacity="0.6">
                    <animate attributeName="opacity" values="0.6;0.1;0.6" dur="1.2s" repeatCount="indefinite" />
                </circle>
                {/* Eyes — X eyes (broken) */}
                <g transform="translate(188, 90)">
                    <line x1="-4" y1="-4" x2="4" y2="4" stroke="#EF4444" strokeWidth="2" strokeLinecap="round" />
                    <line x1="4" y1="-4" x2="-4" y2="4" stroke="#EF4444" strokeWidth="2" strokeLinecap="round" />
                </g>
                <g transform="translate(212, 90)">
                    <line x1="-4" y1="-4" x2="4" y2="4" stroke="#EF4444" strokeWidth="2" strokeLinecap="round" />
                    <line x1="4" y1="-4" x2="-4" y2="4" stroke="#EF4444" strokeWidth="2" strokeLinecap="round" />
                </g>
                {/* Mouth — zigzag (broken) */}
                <path d="M190 105 L195 100 L200 105 L205 100 L210 105" stroke="#94A3B8" strokeWidth="1.5" fill="none" strokeLinecap="round" />
            </g>

            {/* Robot arms — dangling */}
            <path d="M160 140 L130 170 L125 190" stroke="#CBD5E1" strokeWidth="4" strokeLinecap="round" strokeLinejoin="round" fill="none" />
            <circle cx="123" cy="192" r="6" fill="#E2E8F0" stroke="#CBD5E1" strokeWidth="1.5" />
            <path d="M240 140 L270 165 L275 185" stroke="#CBD5E1" strokeWidth="4" strokeLinecap="round" strokeLinejoin="round" fill="none" />
            <circle cx="277" cy="187" r="6" fill="#E2E8F0" stroke="#CBD5E1" strokeWidth="1.5" />

            {/* Robot legs */}
            <rect x="175" y="220" width="14" height="35" rx="5" fill="#CBD5E1" />
            <rect x="211" y="220" width="14" height="35" rx="5" fill="#CBD5E1" />
            <rect x="172" y="252" width="20" height="8" rx="4" fill="#94A3B8" />
            <rect x="208" y="252" width="20" height="8" rx="4" fill="#94A3B8" />

            {/* Sparks / lightning */}
            <path d="M145 100 L150 110 L145 110 L152 125" stroke="#F59E0B" strokeWidth="2" strokeLinecap="round" fill="none">
                <animate attributeName="opacity" values="1;0;1" dur="0.8s" repeatCount="indefinite" />
            </path>
            <path d="M255 85 L260 95 L255 95 L262 108" stroke="#F59E0B" strokeWidth="2" strokeLinecap="round" fill="none">
                <animate attributeName="opacity" values="0;1;0" dur="1s" repeatCount="indefinite" />
            </path>

            {/* Smoke puffs */}
            <circle cx="230" cy="75" r="8" fill="#94A3B8" opacity="0.15">
                <animate attributeName="cy" values="75;65;55" dur="3s" repeatCount="indefinite" />
                <animate attributeName="opacity" values="0.15;0.05;0" dur="3s" repeatCount="indefinite" />
            </circle>
            <circle cx="240" cy="80" r="6" fill="#94A3B8" opacity="0.1">
                <animate attributeName="cy" values="80;68;56" dur="3.5s" repeatCount="indefinite" />
                <animate attributeName="opacity" values="0.1;0.03;0" dur="3.5s" repeatCount="indefinite" />
            </circle>

            {/* Scattered bolts on ground */}
            <rect x="130" y="258" width="8" height="3" rx="1" fill="#94A3B8" opacity="0.4" transform="rotate(25 134 259)" />
            <rect x="270" y="255" width="6" height="3" rx="1" fill="#94A3B8" opacity="0.3" transform="rotate(-15 273 256)" />
            <circle cx="150" cy="260" r="2" fill="#CBD5E1" opacity="0.4" />
        </svg>
    );
}

/** 503 — Construction scene with barriers and worker */
function Illustration503() {
    return (
        <svg viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full max-w-[360px]">
            <defs>
                <linearGradient id="sky503" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#F9FAFB" />
                    <stop offset="100%" stopColor="#F3F4F6" />
                </linearGradient>
            </defs>

            <rect width="400" height="300" fill="url(#sky503)" />
            <ellipse cx="200" cy="262" rx="170" ry="18" fill="#E5E7EB" opacity="0.3" />

            <text x="200" y="160" textAnchor="middle" fontSize="120" fontWeight="900" fill="#6B7280" opacity="0.04" fontFamily="system-ui, sans-serif">503</text>

            {/* Construction barrier left */}
            <rect x="80" y="180" width="8" height="80" rx="2" fill="#F59E0B" />
            <rect x="80" y="175" width="70" height="14" rx="3" fill="#F59E0B" />
            <rect x="80" y="175" width="70" height="14" rx="3" fill="url(#stripes503)" />
            <rect x="142" y="180" width="8" height="80" rx="2" fill="#F59E0B" />

            {/* Construction barrier right */}
            <rect x="260" y="180" width="8" height="80" rx="2" fill="#F59E0B" />
            <rect x="260" y="175" width="70" height="14" rx="3" fill="#F59E0B" />
            <rect x="322" y="180" width="8" height="80" rx="2" fill="#F59E0B" />

            {/* Stripes on barriers */}
            {[0, 1, 2, 3].map(i => (
                <rect key={`sl${i}`} x={88 + i * 18} y="175" width="9" height="14" fill="#292524" opacity="0.15" />
            ))}
            {[0, 1, 2, 3].map(i => (
                <rect key={`sr${i}`} x={268 + i * 18} y="175" width="9" height="14" fill="#292524" opacity="0.15" />
            ))}

            {/* Traffic cone center */}
            <path d="M195 258 L200 210 L205 258 Z" fill="#F97316" />
            <rect x="190" y="255" width="20" height="6" rx="2" fill="#EA580C" />
            <rect x="196" y="225" width="8" height="4" rx="1" fill="white" opacity="0.6" />
            <rect x="197" y="240" width="6" height="3" rx="1" fill="white" opacity="0.5" />

            {/* Large gear (being worked on) */}
            <g transform="translate(200, 120)">
                <circle r="40" stroke="#D1D5DB" strokeWidth="3" fill="#F9FAFB" strokeDasharray="12 8">
                    <animateTransform attributeName="transform" type="rotate" from="0" to="360" dur="15s" repeatCount="indefinite" />
                </circle>
                <circle r="18" fill="#E5E7EB" />
                <circle r="8" fill="#9CA3AF" />
                {/* Gear teeth */}
                {[0, 45, 90, 135, 180, 225, 270, 315].map(angle => (
                    <rect key={angle} x="-5" y="-44" width="10" height="10" rx="2" fill="#D1D5DB" transform={`rotate(${angle})`}>
                        <animateTransform attributeName="transform" type="rotate" from={`${angle}`} to={`${angle + 360}`} dur="15s" repeatCount="indefinite" />
                    </rect>
                ))}
            </g>

            {/* Wrench leaning on gear */}
            <g transform="translate(245, 145) rotate(30)">
                <rect x="0" y="-3" width="45" height="6" rx="3" fill="#6B7280" />
                <circle cx="0" cy="0" r="10" stroke="#6B7280" strokeWidth="3" fill="none" />
                <rect x="-3" y="-12" width="6" height="6" fill="#F9FAFB" />
            </g>

            {/* Progress bar at bottom */}
            <rect x="120" y="275" width="160" height="6" rx="3" fill="#E5E7EB" />
            <rect x="120" y="275" rx="3" height="6" fill="#6B7280" opacity="0.5">
                <animate attributeName="width" values="20;140;20" dur="3s" repeatCount="indefinite" />
            </rect>

            {/* Floating particles */}
            <circle cx="60" cy="80" r="3" fill="#D1D5DB" opacity="0.4">
                <animate attributeName="cy" values="80;72;80" dur="3s" repeatCount="indefinite" />
            </circle>
            <circle cx="340" cy="100" r="4" fill="#E5E7EB" opacity="0.3">
                <animate attributeName="cy" values="100;92;100" dur="2.5s" repeatCount="indefinite" />
            </circle>
        </svg>
    );
}

/** 419 — Hourglass with person waiting */
function Illustration419() {
    return (
        <svg viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full max-w-[360px]">
            <defs>
                <linearGradient id="sky419" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#FFFBEB" />
                    <stop offset="100%" stopColor="#FEF3C7" />
                </linearGradient>
            </defs>

            <rect width="400" height="300" fill="url(#sky419)" />
            <ellipse cx="200" cy="262" rx="170" ry="18" fill="#FDE68A" opacity="0.2" />

            <text x="200" y="160" textAnchor="middle" fontSize="120" fontWeight="900" fill="#D97706" opacity="0.05" fontFamily="system-ui, sans-serif">419</text>

            {/* Large hourglass */}
            {/* Frame top */}
            <rect x="170" y="60" width="60" height="8" rx="4" fill="#92400E" opacity="0.6" />
            {/* Frame bottom */}
            <rect x="170" y="232" width="60" height="8" rx="4" fill="#92400E" opacity="0.6" />
            {/* Glass body */}
            <path d="M178 68 L178 130 L200 155 L222 130 L222 68" fill="#FEF9C3" stroke="#D97706" strokeWidth="2" opacity="0.6" />
            <path d="M178 232 L178 170 L200 155 L222 170 L222 232" fill="#FEF9C3" stroke="#D97706" strokeWidth="2" opacity="0.6" />
            {/* Sand top (depleting) */}
            <path d="M182 90 L218 90 L205 130 L195 130 Z" fill="#FBBF24" opacity="0.5">
                <animate attributeName="opacity" values="0.5;0.15;0.5" dur="4s" repeatCount="indefinite" />
            </path>
            {/* Sand bottom (accumulating) */}
            <path d="M185 225 L215 225 L210 195 L190 195 Z" fill="#FBBF24" opacity="0.7" />
            {/* Sand stream */}
            <line x1="200" y1="135" x2="200" y2="190" stroke="#D97706" strokeWidth="1.5" opacity="0.4">
                <animate attributeName="opacity" values="0.4;0.1;0.4" dur="1.5s" repeatCount="indefinite" />
            </line>

            {/* Person sitting and waiting */}
            <ellipse cx="100" cy="253" rx="18" ry="4" fill="#92400E" opacity="0.1" />
            {/* Sitting legs */}
            <path d="M90 235 L85 250" stroke="#292524" strokeWidth="3" strokeLinecap="round" />
            <path d="M110 235 L115 250" stroke="#292524" strokeWidth="3" strokeLinecap="round" />
            <ellipse cx="83" cy="252" rx="6" ry="2.5" fill="#292524" />
            <ellipse cx="117" cy="252" rx="6" ry="2.5" fill="#292524" />
            {/* Torso — slouched */}
            <path d="M85 210 C85 220 90 235 100 237 C110 235 115 220 115 210 Z" fill="#D97706" />
            {/* Arms — resting on knees */}
            <path d="M85 218 L80 230" stroke="#D97706" strokeWidth="3" strokeLinecap="round" fill="none" />
            <path d="M115 218 L120 230" stroke="#D97706" strokeWidth="3" strokeLinecap="round" fill="none" />
            {/* Head — resting on hand */}
            <circle cx="100" cy="196" r="15" fill="#FCD9B6" />
            <path d="M86 191 C86 180 92 176 100 176 C108 176 114 180 114 191" fill="#292524" />
            {/* Sleepy eyes */}
            <line x1="93" y1="198" x2="99" y2="198" stroke="#292524" strokeWidth="1.5" strokeLinecap="round" />
            <line x1="103" y1="198" x2="109" y2="198" stroke="#292524" strokeWidth="1.5" strokeLinecap="round" />
            {/* Mouth — yawn */}
            <ellipse cx="100" cy="205" rx="3" ry="2" fill="#C4846C" opacity="0.6" />

            {/* Zzz floating */}
            <text x="120" y="185" fontSize="11" fill="#D97706" opacity="0.4" fontFamily="system-ui" fontWeight="600">z
                <animate attributeName="y" values="185;178;185" dur="2s" repeatCount="indefinite" />
            </text>
            <text x="130" y="175" fontSize="14" fill="#D97706" opacity="0.3" fontFamily="system-ui" fontWeight="600">z
                <animate attributeName="y" values="175;166;175" dur="2.5s" repeatCount="indefinite" />
            </text>
            <text x="142" y="165" fontSize="17" fill="#D97706" opacity="0.2" fontFamily="system-ui" fontWeight="600">Z
                <animate attributeName="y" values="165;155;165" dur="3s" repeatCount="indefinite" />
            </text>

            {/* Clock on wall */}
            <circle cx="310" cy="90" r="22" fill="white" stroke="#D1D5DB" strokeWidth="2" />
            <line x1="310" y1="90" x2="310" y2="76" stroke="#6B7280" strokeWidth="2" strokeLinecap="round" />
            <line x1="310" y1="90" x2="320" y2="95" stroke="#6B7280" strokeWidth="1.5" strokeLinecap="round" />
            <circle cx="310" cy="90" r="2" fill="#6B7280" />

            {/* Floating dots */}
            <circle cx="55" cy="100" r="3" fill="#FDE68A" opacity="0.5">
                <animate attributeName="cy" values="100;93;100" dur="3s" repeatCount="indefinite" />
            </circle>
            <circle cx="350" cy="200" r="4" fill="#FCD34D" opacity="0.3">
                <animate attributeName="cy" values="200;193;200" dur="2.5s" repeatCount="indefinite" />
            </circle>
        </svg>
    );
}

/* ═══════════════════════════════════════════════════════════════════════════
   ERROR CONFIGURATION
   ═══════════════════════════════════════════════════════════════════════════ */

const ERROR_CONFIG: Record<number, {
    title: string;
    description: string;
    illustration: () => React.JSX.Element;
    accent: string;
    accentLight: string;
    buttonBg: string;
    buttonHover: string;
    buttonRing: string;
}> = {
    403: {
        title: 'Access Denied',
        description: 'You don\'t have permission to access this page. If you believe this is a mistake, please contact your administrator.',
        illustration: Illustration403,
        accent: 'text-orange-600',
        accentLight: 'bg-orange-50',
        buttonBg: 'bg-orange-600',
        buttonHover: 'hover:bg-orange-700',
        buttonRing: 'focus:ring-orange-500',
    },
    404: {
        title: 'Page Not Found',
        description: 'The page you\'re looking for doesn\'t exist or has been moved to a different location.',
        illustration: Illustration404,
        accent: 'text-blue-600',
        accentLight: 'bg-blue-50',
        buttonBg: 'bg-blue-600',
        buttonHover: 'hover:bg-blue-700',
        buttonRing: 'focus:ring-blue-500',
    },
    419: {
        title: 'Session Expired',
        description: 'Your session has timed out for security reasons. Please log in again to continue where you left off.',
        illustration: Illustration419,
        accent: 'text-amber-600',
        accentLight: 'bg-amber-50',
        buttonBg: 'bg-amber-600',
        buttonHover: 'hover:bg-amber-700',
        buttonRing: 'focus:ring-amber-500',
    },
    500: {
        title: 'Something Went Wrong',
        description: 'We encountered an unexpected error. Our team has been notified and is working on a fix.',
        illustration: Illustration500,
        accent: 'text-red-600',
        accentLight: 'bg-red-50',
        buttonBg: 'bg-red-600',
        buttonHover: 'hover:bg-red-700',
        buttonRing: 'focus:ring-red-500',
    },
    503: {
        title: 'Under Maintenance',
        description: 'We\'re performing scheduled maintenance to improve your experience. We\'ll be back shortly.',
        illustration: Illustration503,
        accent: 'text-gray-600',
        accentLight: 'bg-gray-50',
        buttonBg: 'bg-gray-700',
        buttonHover: 'hover:bg-gray-800',
        buttonRing: 'focus:ring-gray-500',
    },
};

/* ═══════════════════════════════════════════════════════════════════════════
   ERROR PAGE COMPONENT
   ═══════════════════════════════════════════════════════════════════════════ */

export default function ErrorPage({ status, message }: ErrorPageProps) {
    const config = ERROR_CONFIG[status] ?? {
        title: 'Error',
        description: message ?? 'An unexpected error occurred.',
        illustration: Illustration500,
        accent: 'text-red-600',
        accentLight: 'bg-red-50',
        buttonBg: 'bg-red-600',
        buttonHover: 'hover:bg-red-700',
        buttonRing: 'focus:ring-red-500',
    };

    const Illustration = config.illustration;

    return (
        <>
            <Head title={`${status} - ${config.title}`} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-white px-4">
                {/* Illustration — floating directly on white, no frame */}
                <div className="mb-6">
                    <Illustration />
                </div>

                {/* Content — clean typography, no card */}
                <div className={`mb-1 text-xs font-bold uppercase tracking-[0.2em] ${config.accent} opacity-50`}>
                    Error {status}
                </div>
                <h1 className="mb-3 text-3xl font-bold tracking-tight text-slate-900">
                    {config.title}
                </h1>
                <p className="mx-auto mb-10 max-w-md text-center text-sm leading-relaxed text-slate-400">
                    {message ?? config.description}
                </p>

                {/* Action Buttons */}
                <div className="flex flex-wrap items-center justify-center gap-3">
                    <button
                        onClick={() => window.history.back()}
                        className="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-6 py-2.5 text-sm font-medium text-slate-600 transition-all hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-2"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Go Back
                    </button>
                    <button
                        onClick={() => router.visit('/dashboard')}
                        className={`inline-flex items-center justify-center gap-2 rounded-full border border-transparent px-6 py-2.5 text-sm font-medium text-white transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 ${config.buttonBg} ${config.buttonHover} ${config.buttonRing}`}
                    >
                        <Home className="h-4 w-4" />
                        Dashboard
                    </button>
                    {status === 419 && (
                        <button
                            onClick={() => (window.location.href = '/login')}
                            className="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-6 py-2.5 text-sm font-medium text-slate-600 transition-all hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-2"
                        >
                            <LogIn className="h-4 w-4" />
                            Log In
                        </button>
                    )}
                    {status === 500 && (
                        <button
                            onClick={() => window.location.reload()}
                            className="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-6 py-2.5 text-sm font-medium text-slate-600 transition-all hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-2"
                        >
                            <RefreshCw className="h-4 w-4" />
                            Retry
                        </button>
                    )}
                </div>

                {/* Footer — subtle, far below */}
                <p className="mt-16 text-xs text-slate-300">
                    If this problem persists, please contact{' '}
                    <a href="/docs-help" className="text-slate-400 underline decoration-slate-200 underline-offset-2 transition-colors hover:text-slate-600">
                        IT Support
                    </a>
                </p>
            </div>
        </>
    );
}

ErrorPage.layout = (page: ReactNode) => page;
