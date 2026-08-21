import { BarChart, LineChart } from "echarts/charts"
import {
  GridComponent,
  LegendComponent,
  TooltipComponent,
} from "echarts/components"
import { use } from "echarts/core"
import { CanvasRenderer } from "echarts/renderers"

// Only the components the three charts actually use; anything added to a chart
// option must be registered here too.
use([
  BarChart,
  LineChart,
  CanvasRenderer,
  GridComponent,
  LegendComponent,
  TooltipComponent,
])
