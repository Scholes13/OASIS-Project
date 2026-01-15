import * as React from "react"
import { Listbox, Transition } from "@headlessui/react"
import { Check, ChevronDown } from "lucide-react"
import { cn } from "@/lib/utils"

export interface SelectOption {
  value: string | number
  label: string
  disabled?: boolean
}

export interface SelectProps {
  value?: string | number | null
  onChange?: (value: string | number) => void
  options: SelectOption[]
  placeholder?: string
  disabled?: boolean
  error?: string
  className?: string
  label?: string
  required?: boolean
}

export function Select({
  value,
  onChange,
  options,
  placeholder = "Select option...",
  disabled = false,
  error,
  className,
  label,
  required,
}: SelectProps) {
  const selectedOption = options.find((opt) => opt.value === value)

  return (
    <div className={cn("w-full", className)}>
      {label && (
        <label className="mb-1.5 block text-sm font-medium text-gray-700">
          {label}
          {required && <span className="ml-1 text-red-500">*</span>}
        </label>
      )}
      <Listbox value={value} onChange={onChange} disabled={disabled}>
        <div className="relative">
          <Listbox.Button
            className={cn(
              "relative w-full cursor-pointer rounded-md border bg-white py-2 pl-3 pr-10 text-left text-sm shadow-sm transition-colors",
              "focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500",
              disabled && "cursor-not-allowed opacity-50 bg-gray-50",
              error ? "border-red-500" : "border-gray-300"
            )}
          >
            <span className={cn("block truncate", !selectedOption && "text-gray-400")}>
              {selectedOption?.label || placeholder}
            </span>
            <span className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
              <ChevronDown className="h-4 w-4 text-gray-400" aria-hidden="true" />
            </span>
          </Listbox.Button>

          <Transition
            as={React.Fragment}
            leave="transition ease-in duration-100"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <Listbox.Options className="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
              {options.map((option) => (
                <Listbox.Option
                  key={option.value}
                  value={option.value}
                  disabled={option.disabled}
                  className={({ active, selected }) =>
                    cn(
                      "relative cursor-pointer select-none py-2 pl-10 pr-4",
                      active && "bg-indigo-50 text-indigo-900",
                      selected && "bg-indigo-100",
                      option.disabled && "cursor-not-allowed opacity-50"
                    )
                  }
                >
                  {({ selected }) => (
                    <>
                      <span className={cn("block truncate", selected && "font-medium")}>
                        {option.label}
                      </span>
                      {selected && (
                        <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-indigo-600">
                          <Check className="h-4 w-4" aria-hidden="true" />
                        </span>
                      )}
                    </>
                  )}
                </Listbox.Option>
              ))}
            </Listbox.Options>
          </Transition>
        </div>
      </Listbox>
      {error && <p className="mt-1 text-xs text-red-500">{error}</p>}
    </div>
  )
}

// Multi-select variant
export interface MultiSelectProps {
  value?: (string | number)[]
  onChange?: (value: (string | number)[]) => void
  options: SelectOption[]
  placeholder?: string
  disabled?: boolean
  error?: string
  className?: string
  label?: string
  required?: boolean
}

export function MultiSelect({
  value = [],
  onChange,
  options,
  placeholder = "Select options...",
  disabled = false,
  error,
  className,
  label,
  required,
}: MultiSelectProps) {
  const selectedOptions = options.filter((opt) => value.includes(opt.value))

  const handleChange = (newValue: (string | number)[]) => {
    onChange?.(newValue)
  }

  return (
    <div className={cn("w-full", className)}>
      {label && (
        <label className="mb-1.5 block text-sm font-medium text-gray-700">
          {label}
          {required && <span className="ml-1 text-red-500">*</span>}
        </label>
      )}
      <Listbox value={value} onChange={handleChange} disabled={disabled} multiple>
        <div className="relative">
          <Listbox.Button
            className={cn(
              "relative w-full cursor-pointer rounded-md border bg-white py-2 pl-3 pr-10 text-left text-sm shadow-sm transition-colors",
              "focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500",
              disabled && "cursor-not-allowed opacity-50 bg-gray-50",
              error ? "border-red-500" : "border-gray-300"
            )}
          >
            <span className={cn("block truncate", selectedOptions.length === 0 && "text-gray-400")}>
              {selectedOptions.length > 0
                ? selectedOptions.map((o) => o.label).join(", ")
                : placeholder}
            </span>
            <span className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
              <ChevronDown className="h-4 w-4 text-gray-400" aria-hidden="true" />
            </span>
          </Listbox.Button>

          <Transition
            as={React.Fragment}
            leave="transition ease-in duration-100"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <Listbox.Options className="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
              {options.map((option) => (
                <Listbox.Option
                  key={option.value}
                  value={option.value}
                  disabled={option.disabled}
                  className={({ active, selected }) =>
                    cn(
                      "relative cursor-pointer select-none py-2 pl-10 pr-4",
                      active && "bg-indigo-50 text-indigo-900",
                      selected && "bg-indigo-100",
                      option.disabled && "cursor-not-allowed opacity-50"
                    )
                  }
                >
                  {({ selected }) => (
                    <>
                      <span className={cn("block truncate", selected && "font-medium")}>
                        {option.label}
                      </span>
                      {selected && (
                        <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-indigo-600">
                          <Check className="h-4 w-4" aria-hidden="true" />
                        </span>
                      )}
                    </>
                  )}
                </Listbox.Option>
              ))}
            </Listbox.Options>
          </Transition>
        </div>
      </Listbox>
      {error && <p className="mt-1 text-xs text-red-500">{error}</p>}
    </div>
  )
}
