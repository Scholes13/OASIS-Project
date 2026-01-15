import * as React from "react"
import {
  LineChart as RechartsLineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend,
} from "recharts"
import { cn } from "@/lib/utils"

interface LineChartProps {
  data: Array<Record<string, any>>
  lines: Array<{
    key: string
    label: string
    color: string
    strokeWidth?: number
    dashed?: boolean
  }>
  xAxisKey: string
  title?: string
  description?: string
  className?: string
  height?: number
  showGrid?: boolean
  showLegend?: boolean
  showDots?: boolean
  valueFormatter?: (value: number) => string
  curved?: boolean
}

export function LineChart({
  data,
  lines,
  xAxisKey,
  title,
  description,
  className,
  height = 300,
  showGrid = true,
  showLegend = true,
  showDots = true,
  valueFormatter = (v) => v.toString(),
  curved = true,
}: LineChartProps) {
  return (
    <div className={cn("w-full", className)}>
      {(title || description) && (
        <div className="mb-4">
          {title && <h3 className="text-sm font-semibold text-gray-900">{title}</h3>}
          {description && <p className="text-sm text-gray-500">{description}</p>}
        </div>
      )}
      <ResponsiveContainer width="100%" height={height}>
        <RechartsLineChart
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
                        className="h-3 w-3 rounded-full"
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
          {lines.map((line) => (
            <Line
              key={line.key}
              type={curved ? "monotone" : "linear"}
              dataKey={line.key}
              name={line.label}
              stroke={line.color}
              strokeWidth={line.strokeWidth || 2}
              strokeDasharray={line.dashed ? "5 5" : undefined}
              dot={showDots ? { fill: line.color, strokeWidth: 2, r: 4 } : false}
              activeDot={{ r: 6, strokeWidth: 2 }}
            />
          ))}
        </RechartsLineChart>
      </ResponsiveContainer>
    </div>
  )
}

// Area Chart variant
import { AreaChart as RechartsAreaChart, Area } from "recharts"

interface AreaChartProps extends Omit<LineChartProps, "lines" | "showDots"> {
  areas: Array<{
    key: string
    label: string
    color: string
    fillOpacity?: number
  }>
  stacked?: boolean
}

export function AreaChart({
  data,
  areas,
  xAxisKey,
  title,
  description,
  className,
  height = 300,
  showGrid = true,
  showLegend = true,
  valueFormatter = (v) => v.toString(),
  curved = true,
  stacked = false,
}: AreaChartProps) {
  return (
    <div className={cn("w-full", className)}>
      {(title || description) && (
        <div className="mb-4">
          {title && <h3 className="text-sm font-semibold text-gray-900">{title}</h3>}
          {description && <p className="text-sm text-gray-500">{description}</p>}
        </div>
      )}
      <ResponsiveContainer width="100%" height={height}>
        <RechartsAreaChart
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
          {areas.map((area) => (
            <Area
              key={area.key}
              type={curved ? "monotone" : "linear"}
              dataKey={area.key}
              name={area.label}
              stroke={area.color}
              fill={area.color}
              fillOpacity={area.fillOpacity || 0.3}
              stackId={stacked ? "stack" : undefined}
            />
          ))}
        </RechartsAreaChart>
      </ResponsiveContainer>
    </div>
  )
}
