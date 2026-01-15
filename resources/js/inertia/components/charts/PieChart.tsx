import * as React from "react"
import {
  PieChart as RechartsPieChart,
  Pie,
  Cell,
  Tooltip,
  ResponsiveContainer,
  Legend,
} from "recharts"
import { cn } from "@/lib/utils"

interface PieChartProps {
  data: Array<{
    name: string
    value: number
    color?: string
  }>
  title?: string
  description?: string
  className?: string
  height?: number
  showLegend?: boolean
  showLabels?: boolean
  innerRadius?: number
  outerRadius?: number
  valueFormatter?: (value: number) => string
  colors?: string[]
}

const defaultColors = [
  "#6366f1", // indigo
  "#22c55e", // green
  "#f59e0b", // amber
  "#ef4444", // red
  "#8b5cf6", // violet
  "#06b6d4", // cyan
  "#ec4899", // pink
  "#84cc16", // lime
]

export function PieChart({
  data,
  title,
  description,
  className,
  height = 300,
  showLegend = true,
  showLabels = false,
  innerRadius = 0,
  outerRadius = 80,
  valueFormatter = (v) => v.toString(),
  colors = defaultColors,
}: PieChartProps) {
  const total = data.reduce((sum, item) => sum + item.value, 0)

  const renderLabel = ({ cx, cy, midAngle, innerRadius, outerRadius, percent }: any) => {
    if (!showLabels) return null
    const RADIAN = Math.PI / 180
    const radius = innerRadius + (outerRadius - innerRadius) * 0.5
    const x = cx + radius * Math.cos(-midAngle * RADIAN)
    const y = cy + radius * Math.sin(-midAngle * RADIAN)

    return percent > 0.05 ? (
      <text
        x={x}
        y={y}
        fill="white"
        textAnchor="middle"
        dominantBaseline="central"
        fontSize={12}
        fontWeight={500}
      >
        {`${(percent * 100).toFixed(0)}%`}
      </text>
    ) : null
  }

  return (
    <div className={cn("w-full", className)}>
      {(title || description) && (
        <div className="mb-4">
          {title && <h3 className="text-sm font-semibold text-gray-900">{title}</h3>}
          {description && <p className="text-sm text-gray-500">{description}</p>}
        </div>
      )}
      <ResponsiveContainer width="100%" height={height}>
        <RechartsPieChart>
          <Pie
            data={data}
            cx="50%"
            cy="50%"
            innerRadius={innerRadius}
            outerRadius={outerRadius}
            paddingAngle={2}
            dataKey="value"
            label={renderLabel}
            labelLine={false}
          >
            {data.map((entry, index) => (
              <Cell
                key={`cell-${index}`}
                fill={entry.color || colors[index % colors.length]}
              />
            ))}
          </Pie>
          <Tooltip
            content={({ active, payload }) => {
              if (!active || !payload?.length) return null
              const item = payload[0].payload
              const percentage = ((item.value / total) * 100).toFixed(1)
              return (
                <div className="rounded-lg border bg-white p-2 shadow-md">
                  <div className="flex items-center gap-2">
                    <span
                      className="h-3 w-3 rounded"
                      style={{ backgroundColor: item.color || colors[0] }}
                    />
                    <span className="text-sm font-medium text-gray-900">{item.name}</span>
                  </div>
                  <p className="text-sm text-gray-600 mt-1">
                    {valueFormatter(item.value)} ({percentage}%)
                  </p>
                </div>
              )
            }}
          />
          {showLegend && (
            <Legend
              layout="vertical"
              align="right"
              verticalAlign="middle"
              formatter={(value: string) => (
                <span className="text-sm text-gray-600">{value}</span>
              )}
            />
          )}
        </RechartsPieChart>
      </ResponsiveContainer>
    </div>
  )
}

// Donut Chart (Pie with inner radius)
interface DonutChartProps extends Omit<PieChartProps, "innerRadius"> {
  centerLabel?: string
  centerValue?: string | number
}

export function DonutChart({
  data,
  title,
  description,
  className,
  height = 300,
  showLegend = true,
  outerRadius = 80,
  valueFormatter = (v) => v.toString(),
  colors = defaultColors,
  centerLabel,
  centerValue,
}: DonutChartProps) {
  const total = data.reduce((sum, item) => sum + item.value, 0)

  return (
    <div className={cn("w-full", className)}>
      {(title || description) && (
        <div className="mb-4">
          {title && <h3 className="text-sm font-semibold text-gray-900">{title}</h3>}
          {description && <p className="text-sm text-gray-500">{description}</p>}
        </div>
      )}
      <ResponsiveContainer width="100%" height={height}>
        <RechartsPieChart>
          <Pie
            data={data}
            cx="50%"
            cy="50%"
            innerRadius={outerRadius * 0.6}
            outerRadius={outerRadius}
            paddingAngle={2}
            dataKey="value"
          >
            {data.map((entry, index) => (
              <Cell
                key={`cell-${index}`}
                fill={entry.color || colors[index % colors.length]}
              />
            ))}
          </Pie>
          <Tooltip
            content={({ active, payload }) => {
              if (!active || !payload?.length) return null
              const item = payload[0].payload
              const percentage = ((item.value / total) * 100).toFixed(1)
              return (
                <div className="rounded-lg border bg-white p-2 shadow-md">
                  <div className="flex items-center gap-2">
                    <span
                      className="h-3 w-3 rounded"
                      style={{ backgroundColor: item.color || colors[0] }}
                    />
                    <span className="text-sm font-medium text-gray-900">{item.name}</span>
                  </div>
                  <p className="text-sm text-gray-600 mt-1">
                    {valueFormatter(item.value)} ({percentage}%)
                  </p>
                </div>
              )
            }}
          />
          {showLegend && (
            <Legend
              layout="vertical"
              align="right"
              verticalAlign="middle"
              formatter={(value: string) => (
                <span className="text-sm text-gray-600">{value}</span>
              )}
            />
          )}
          {/* Center text */}
          {(centerLabel || centerValue) && (
            <text
              x="50%"
              y="50%"
              textAnchor="middle"
              dominantBaseline="middle"
            >
              {centerValue && (
                <tspan
                  x="50%"
                  dy="-0.5em"
                  className="text-2xl font-bold"
                  fill="#111827"
                >
                  {centerValue}
                </tspan>
              )}
              {centerLabel && (
                <tspan
                  x="50%"
                  dy="1.5em"
                  className="text-sm"
                  fill="#6b7280"
                >
                  {centerLabel}
                </tspan>
              )}
            </text>
          )}
        </RechartsPieChart>
      </ResponsiveContainer>
    </div>
  )
}
