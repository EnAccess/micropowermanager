import {
  AREA_GRADIENT_FROM,
  AREA_GRADIENT_TO,
  AXIS_LABEL_X,
  AXIS_LABEL_Y,
  AXIS_LINE,
  CHART_PRIMARY,
  CHART_SERIES,
  SPLIT_LINE,
} from "@/design/palette.js"

// ECharts draws on canvas, so its text does not inherit the page font.
const TEXT_STYLE = {
  fontFamily: '"Roboto", "Helvetica", "Arial", sans-serif',
  fontWeight: 300,
  fontSize: 12,
}

const axisCommon = (labels, formatAxisValue) => ({
  xAxis: {
    type: "category",
    data: labels,
    axisLine: { lineStyle: { color: AXIS_LINE } },
    axisTick: { show: false },
    axisLabel: { color: AXIS_LABEL_X, ...TEXT_STYLE },
  },
  yAxis: {
    type: "value",
    splitLine: { lineStyle: { color: SPLIT_LINE } },
    axisLabel: {
      color: AXIS_LABEL_Y,
      ...TEXT_STYLE,
      formatter: formatAxisValue,
    },
  },
})

const tooltip = (formatValue) => ({
  trigger: "axis",
  axisPointer: { type: "shadow" },
  valueFormatter: formatValue,
  textStyle: TEXT_STYLE,
})

export const heroCountOption = ({
  labels,
  counts,
  formatValue,
  formatAxisValue,
}) => ({
  grid: { left: 8, right: 8, top: 20, bottom: 8, containLabel: true },
  ...axisCommon(labels, formatAxisValue),
  tooltip: tooltip(formatValue),
  series: [
    {
      type: "bar",
      barWidth: "50%",
      itemStyle: { borderRadius: [3, 3, 0, 0], color: CHART_PRIMARY },
      data: counts,
    },
  ],
})

/**
 * Series colours are applied positionally from the brand palette, so the stacked
 * order stays stable however many providers the API returns.
 */
export const heroProviderOption = ({
  labels,
  series,
  formatValue,
  formatAxisValue,
}) => ({
  color: CHART_SERIES,
  grid: { left: 8, right: 8, top: 40, bottom: 8, containLabel: true },
  ...axisCommon(labels, formatAxisValue),
  tooltip: tooltip(formatValue),
  legend: {
    top: 6,
    left: "center",
    itemWidth: 12,
    itemHeight: 12,
    textStyle: { color: "#495057", ...TEXT_STYLE },
    data: series.map((entry) => entry.name),
  },
  series: series.map((entry, index) => ({
    name: entry.name,
    type: "bar",
    stack: "tx",
    barWidth: "56%",
    itemStyle: {
      borderRadius: index === series.length - 1 ? [3, 3, 0, 0] : [0, 0, 0, 0],
    },
    emphasis: { focus: "series" },
    data: entry.counts,
  })),
})

export const tenantLineOption = ({
  labels,
  counts,
  formatValue,
  formatAxisValue,
}) => ({
  grid: { left: 8, right: 8, top: 18, bottom: 8, containLabel: true },
  ...axisCommon(labels, formatAxisValue),
  tooltip: { ...tooltip(formatValue), axisPointer: undefined },
  series: [
    {
      type: "line",
      smooth: true,
      symbol: "circle",
      symbolSize: 6,
      data: counts,
      lineStyle: { color: CHART_PRIMARY, width: 2.5 },
      itemStyle: {
        color: CHART_PRIMARY,
        borderColor: "#fff",
        borderWidth: 1.5,
      },
      areaStyle: {
        // Declared as a plain gradient object so the option stays serialisable and
        // no ECharts internals need importing.
        color: {
          type: "linear",
          x: 0,
          y: 0,
          x2: 0,
          y2: 1,
          colorStops: [
            { offset: 0, color: AREA_GRADIENT_FROM },
            { offset: 1, color: AREA_GRADIENT_TO },
          ],
        },
      },
    },
  ],
})
