import * as React from "react"
import {
  BarChart as RechartsBarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend,
  Cell,
} from "recharts"
import { cn } from "@/lib/utils"

interface BarChartProps {
  data: Array<{
    name: string
    value: number
    color?: string
  }>
  title?: string
  description?: string
  className?: string
  height?: number
  showGrid?: boolean
  showLegend?: boolean
  valueFormatter?: (value: number) => string
  colors?: string[]
  layout?: "horizontal" | "vertical"
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

export function BarChart({
  data,
  title,
  description,
  className,
  height = 300,
  showGrid = true,
  showLegend = false,
  valueFormatter = (v) => v.toString(),
  colors = defaultColors,
  layout = "horizontal",
}: BarChartProps) {
  return (
    <div className={cn("w-full", className)}>
      {(title || description) && (
        <div className="mb-4">
          {title && <h3 className="text-sm font-semibold text-gray-900">{title}</h3>}
          {description && <p className="text-sm text-gray-500">{description}</p>}
        </div>
      )}
      <ResponsiveContainer width="100%" height={height}>
        <RechartsBarChart
          data={data}
          layout={layout === "vertical" ? "vertical" : "horizontal"}
          margin={{ top: 10, right: 10, left: 10, bottom: 10 }}
        >
          {showGrid && <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />}
          {layout === "horizontal" ? (
            <>
              <XAxis
                dataKey="name"
                tick={{ fill: "#6b7280", fontSize: 12 }}
                tickLine={false}
                axisLine={{ stroke: "#e5e7eb" }}
              />
              <YAxis
                tick={{ fill: "#6b7280", fontSize: 12 }}
                tickLine={false}
                axisLine={false}
                tickFormatter={valueFormatter}
              />
            </>
          ) : (
            <>
              <XAxis
                type="number"
                tick={{ fill: "#6b7280", fontSize: 12 }}
                tickLine={false}
                axisLine={false}
                tickFormatter={valueFormatter}
              />
              <YAxis
                type="category"
                dataKey="name"
                tick={{ fill: "#6b7280", fontSize: 12 }}
                tickLine={false}
                axisLine={{ stroke: "#e5e7eb" }}
                width={100}
              />
            </>
          )}
          <Tooltip
            content={({ active, payload }) => {
              if (!active || !payload?.length) return null
              const data = payload[0].payload
              return (
                <div className="rounded-lg border bg-white p-2 shadow-md">
                  <p className="text-sm font-medium text-gray-900">{data.name}</p>
                  <p className="text-sm text-gray-600">{valueFormatter(data.value)}</p>
                </div>
              )
            }}
          />
          {showLegend && <Legend />}
          <Bar dataKey="value" radius={[4, 4, 0, 0]}>
            {data.map((entry, index) => (
              <Cell
                key={`cell-${index}`}
                fill={entry.color || colors[index % colors.length]}
              />
            ))}
          </Bar>
        </RechartsBarChart>
      </ResponsiveContainer>
    </div>
  )
}

// Stacked Bar Chart
interface StackedBarChartProps {
  data: Array<Record<string, any>>
  keys: Array<{ key: string; label: string; color: string }>
  xAxisKey: string
  title?: string
  description?: string
  className?: string
  height?: number
  showGrid?: boolean
  showLegend?: boolean
  valueFormatter?: (value: number) => string
}

export function StackedBarChart({
  data,
  keys,
  xAxisKey,
  title,
  description,
  className,
  height = 300,
  showGrid = true,
  showLegend = true,
  valueFormatter = (v) => v.toString(),
}: StackedBarChartProps) {
  return (
    <div className={cn("w-full", className)}>
      {(title || description) && (
        <div className="mb-4">
          {title && <h3 className="text-sm font-semibold text-gray-900">{title}</h3>}
          {description && <p className="text-sm text-gray-500">{description}</p>}
        </div>
      )}
      <ResponsiveContainer width="100%" height={height}>
        <RechartsBarChart
          data={data}
          margin={{ top: 10, right: 10, left: 10, bottom: 10 }}
        >
          {showGrid && <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />}
          <XAxis
            dataKey={xAxisKey}
            tick={{ fill: "#6b7280", fontSize: 12 }}
            tickLine={false}
            axisLine={{ stroke: "#e5e7eb" }}
          />
          <YAxis
            tick={{ fill: "#6b7280", fontSize: 12 }}
            tickLine={false}
            axisLine={false}
            tickFormatter={valueFormatter}
          />
          <Tooltip
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              return (
                <div className="rounded-lg border bg-white p-2 shadow-md">
                  <p className="text-sm font-medium text-gray-900 mb-1">{label}</p>
                  {payload.map((entry: any, index: number) => (
                    <div key={index} className="flex items-center gap-2 text-sm">
                      <span
                        className="h-3 w-3 rounded"
                        style={{ backgroundColor: entry.color }}
                      />
                      <span className="text-gray-600">{entry.name}:</span>
                      <span className="font-medium">{valueFormatter(entry.value)}</span>
                    </div>
                  ))}
                </div>
              )
            }}
          />
          {showLegend && <Legend />}
          {keys.map((item, index) => (
            <Bar
              key={item.key}
              dataKey={item.key}
              name={item.label}
              stackId="stack"
              fill={item.color}
              radius={index === keys.length - 1 ? [4, 4, 0, 0] : [0, 0, 0, 0]}
            />
          ))}
        </RechartsBarChart>
      </ResponsiveContainer>
    </div>
  )
}
