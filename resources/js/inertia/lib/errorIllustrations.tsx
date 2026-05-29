/** 404 — Person looking at a broken map / lost in space */
export function Illustration404() {
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
            <rect width="400" height="300" fill="url(#sky404)" />
            <ellipse cx="200" cy="260" rx="170" ry="20" fill="url(#ground404)" />
            <text x="200" y="160" textAnchor="middle" fontSize="120" fontWeight="900" fill="#2563EB" opacity="0.06" fontFamily="system-ui, sans-serif">404</text>
            <rect x="260" y="130" width="6" height="120" rx="3" fill="#94A3B8" />
            <rect x="240" y="125" width="50" height="28" rx="6" fill="#E2E8F0" stroke="#CBD5E1" strokeWidth="1.5" />
            <line x1="252" y1="135" x2="278" y2="135" stroke="#94A3B8" strokeWidth="2" strokeLinecap="round" />
            <line x1="252" y1="143" x2="270" y2="143" stroke="#CBD5E1" strokeWidth="2" strokeLinecap="round" />
            <path d="M275 139 L282 135 L275 131" stroke="#94A3B8" strokeWidth="1.5" fill="none" strokeLinecap="round" />
            <path d="M258 125 L262 138 L256 148 L260 153" stroke="#CBD5E1" strokeWidth="1" fill="none" />
            <ellipse cx="170" cy="248" rx="18" ry="5" fill="#1E40AF" opacity="0.1" />
            <path d="M162 220 L158 248" stroke="#1E3A5F" strokeWidth="3.5" strokeLinecap="round" />
            <path d="M178 220 L182 248" stroke="#1E3A5F" strokeWidth="3.5" strokeLinecap="round" />
            <ellipse cx="156" cy="250" rx="7" ry="3" fill="#1E3A5F" />
            <ellipse cx="184" cy="250" rx="7" ry="3" fill="#1E3A5F" />
            <path d="M155 185 C155 200 160 220 170 222 C180 220 185 200 185 185 Z" fill="#2563EB" />
            <path d="M155 192 L135 205 L130 195" stroke="#2563EB" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" fill="none" />
            <path d="M185 192 L200 180" stroke="#2563EB" strokeWidth="3.5" strokeLinecap="round" fill="none" />
            <circle cx="202" cy="178" r="4" fill="#FBBF7B" />
            <circle cx="170" cy="170" r="18" fill="#FCD9B6" />
            <path d="M153 165 C153 150 162 145 170 145 C178 145 187 150 187 165" fill="#1E3A5F" />
            <path d="M153 165 C155 162 160 160 165 163" fill="#1E3A5F" />
            <circle cx="164" cy="172" r="2" fill="#1E3A5F" />
            <circle cx="176" cy="172" r="2" fill="#1E3A5F" />
            <path d="M161 167 Q164 164 167 167" stroke="#1E3A5F" strokeWidth="1.2" fill="none" />
            <path d="M173 167 Q176 164 179 167" stroke="#1E3A5F" strokeWidth="1.2" fill="none" />
            <path d="M166 180 Q170 178 174 180" stroke="#C4846C" strokeWidth="1.2" fill="none" />
            <rect x="118" y="188" width="18" height="14" rx="2" fill="#FEF3C7" stroke="#FCD34D" strokeWidth="1" transform="rotate(-15 127 195)" />
            <line x1="122" y1="193" x2="132" y2="191" stroke="#FBBF24" strokeWidth="0.8" />
            <line x1="123" y1="197" x2="130" y2="195" stroke="#FDE68A" strokeWidth="0.8" />
            <text x="210" y="165" fontSize="16" fill="#2563EB" opacity="0.5" fontFamily="system-ui" fontWeight="700">?</text>
            <text x="225" y="155" fontSize="12" fill="#60A5FA" opacity="0.4" fontFamily="system-ui" fontWeight="700">?
                <animate attributeName="y" values="155;148;155" dur="3s" repeatCount="indefinite" />
            </text>
            <text x="195" y="150" fontSize="10" fill="#93C5FD" opacity="0.3" fontFamily="system-ui" fontWeight="700">?
                <animate attributeName="y" values="150;143;150" dur="2.5s" repeatCount="indefinite" />
            </text>
            <ellipse cx="60" cy="50" rx="30" ry="12" fill="white" opacity="0.7" />
            <ellipse cx="50" cy="48" rx="18" ry="10" fill="white" opacity="0.7" />
            <ellipse cx="330" cy="70" rx="25" ry="10" fill="white" opacity="0.5" />
            <ellipse cx="340" cy="68" rx="15" ry="8" fill="white" opacity="0.5" />
            <path d="M275 250 Q278 240 282 245" stroke="#86EFAC" strokeWidth="2" fill="none" />
            <path d="M278 250 Q275 238 270 242" stroke="#86EFAC" strokeWidth="2" fill="none" />
            <rect x="276" y="250" width="4" height="6" rx="1" fill="#A78BFA" opacity="0.3" />
        </svg>
    );
}

/** 403 — Person blocked by a large locked gate */
export function Illustration403() {
    return (
        <svg viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full max-w-[360px]">
            <defs>
                <linearGradient id="sky403" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#FFF7ED" />
                    <stop offset="100%" stopColor="#FFEDD5" />
                </linearGradient>
            </defs>
            <rect width="400" height="300" fill="url(#sky403)" />
            <ellipse cx="200" cy="265" rx="180" ry="18" fill="#FED7AA" opacity="0.3" />
            <text x="200" y="160" textAnchor="middle" fontSize="120" fontWeight="900" fill="#EA580C" opacity="0.05" fontFamily="system-ui, sans-serif">403</text>
            <rect x="155" y="80" width="90" height="180" rx="4" fill="#FDE8D0" stroke="#FDBA74" strokeWidth="2" />
            <path d="M165 80 L165 130 Q200 100 235 130 L235 80" fill="#FFF7ED" stroke="#FDBA74" strokeWidth="2" />
            <line x1="180" y1="130" x2="180" y2="260" stroke="#FB923C" strokeWidth="2.5" opacity="0.4" />
            <line x1="200" y1="115" x2="200" y2="260" stroke="#FB923C" strokeWidth="2.5" opacity="0.4" />
            <line x1="220" y1="130" x2="220" y2="260" stroke="#FB923C" strokeWidth="2.5" opacity="0.4" />
            <line x1="165" y1="170" x2="235" y2="170" stroke="#FB923C" strokeWidth="2" opacity="0.3" />
            <line x1="165" y1="210" x2="235" y2="210" stroke="#FB923C" strokeWidth="2" opacity="0.3" />
            <rect x="188" y="178" width="24" height="20" rx="5" fill="#EA580C" opacity="0.8" />
            <path d="M194 178 L194 170 C194 164 198 160 200 160 C202 160 206 164 206 170 L206 178" stroke="#EA580C" strokeWidth="2.5" fill="none" />
            <circle cx="200" cy="189" r="3" fill="#FFF7ED" />
            <line x1="200" y1="192" x2="200" y2="196" stroke="#FFF7ED" strokeWidth="1.5" />
            <ellipse cx="100" cy="253" rx="16" ry="4" fill="#9A3412" opacity="0.1" />
            <path d="M93 228 L90 253" stroke="#292524" strokeWidth="3" strokeLinecap="round" />
            <path d="M107 228 L110 253" stroke="#292524" strokeWidth="3" strokeLinecap="round" />
            <ellipse cx="88" cy="255" rx="6" ry="2.5" fill="#292524" />
            <ellipse cx="112" cy="255" rx="6" ry="2.5" fill="#292524" />
            <path d="M87 195 C87 208 92 228 100 230 C108 228 113 208 113 195 Z" fill="#EA580C" />
            <path d="M87 200 L78 215 L85 220" stroke="#EA580C" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" fill="none" />
            <path d="M113 200 L135 195 L148 190" stroke="#EA580C" strokeWidth="3" strokeLinecap="round" fill="none" />
            <circle cx="150" cy="189" r="3.5" fill="#FBBF7B" />
            <circle cx="100" cy="180" r="16" fill="#FCD9B6" />
            <path d="M85 175 C85 162 92 157 100 157 C108 157 115 162 115 175" fill="#292524" />
            <circle cx="95" cy="178" r="1.8" fill="#292524" />
            <circle cx="105" cy="178" r="1.8" fill="#292524" />
            <circle cx="95.5" cy="177" r="0.6" fill="white" />
            <circle cx="105.5" cy="177" r="0.6" fill="white" />
            <path d="M96 186 Q100 184 104 186" stroke="#C4846C" strokeWidth="1" fill="none" />
            <circle cx="310" cy="120" r="22" fill="#FEE2E2" stroke="#FCA5A5" strokeWidth="2" />
            <line x1="296" y1="120" x2="324" y2="120" stroke="#EF4444" strokeWidth="4" strokeLinecap="round" />
            <circle cx="50" cy="80" r="3" fill="#FDBA74" opacity="0.4">
                <animate attributeName="cy" values="80;74;80" dur="3s" repeatCount="indefinite" />
            </circle>
            <circle cx="350" cy="200" r="4" fill="#FED7AA" opacity="0.3">
                <animate attributeName="cy" values="200;194;200" dur="2.5s" repeatCount="indefinite" />
            </circle>
            <circle cx="320" cy="50" r="2.5" fill="#FDBA74" opacity="0.3">
                <animate attributeName="cy" values="50;44;50" dur="3.5s" repeatCount="indefinite" />
            </circle>
            <ellipse cx="300" cy="258" rx="15" ry="8" fill="#86EFAC" opacity="0.3" />
            <ellipse cx="310" cy="255" rx="10" ry="7" fill="#86EFAC" opacity="0.4" />
        </svg>
    );
}
