import { EventBus } from "@/shared/eventbus"
import Client from "@/repositories/Client/AxiosClient"

export class Battery {
  constructor() {
    this.id = null
    this.mini_grid_id = null
    this.node_id = null
    this.device_id = null
    this.battery_count = null
    this.read_out = null

    this.soc_max = null
    this.soc_average = null
    this.soc_min = null
    this.soc_unit = null

    this.soh_max = null
    this.soh_average = null
    this.soh_min = null
    this.soh_unit = null

    this.d_total = null
    this.d_total_unit = null
    this.d_newly_energy = null
    this.d_newly_energy_unit = null
  }

  fromJson(data) {
    this.id = data["id"]
    this.mini_grid_id = data["mini_grid_id"]
    this.node_id = data["node_id"]
    this.device_id = data["device_id"]
    this.battery_count = data["battery_count"]
    this.read_out = data["read_out"]

    this.soc_max = data["soc_max"]
    this.soc_average = data["soc_average"]
    this.soc_min = data["soc_min"]
    this.soc_unit = data["soc_unit"]

    this.soh_max = data["soh_max"]
    this.soh_average = data["soh_average"]
    this.soh_min = data["soh_min"]
    this.soh_unit = data["soh_unit"]

    this.d_total = data["d_total"]
    this.d_total_unit = data["d_total_unit"]
    this.d_newly_energy = data["d_newly_energy"]
    this.d_newly_energy_unit = data["d_newly_energy_unit"]

    return this
  }
}

export class BatteryService {
  constructor() {
    this.batteryData = []
    this.stateChartData = []
    this.energyChartData = []
    this.subscriber = null
  }

  async getBatteryUsageList(
    miniGridId,
    withChartData = false,
    startDate = null,
    endDate = null,
  ) {
    if (typeof miniGridId === "undefined") {
      return null
    }
    let params = {}
    if (startDate) {
      params["start_date"] = startDate
    }
    if (endDate) {
      params["end_date"] = endDate
    }
    let list = await Client.get(
      `${resources.batteries.detail}${miniGridId}/batteries`,
      { params: params },
    )

    list.data.data.map((battery) =>
      this.fetchBatteryData(battery, withChartData),
    )
    if (withChartData) {
      EventBus.$emit("chartLoaded", this.subscriber)
    }

    return true
  }

  fetchBatteryData(battery, withCartData) {
    this.batteryData.push(new Battery().fromJson(battery))
    if (withCartData) {
      this.chartDataDistributor(battery)
    }
  }

  chartDataDistributor(data) {
    this.prepareStateChartData(data)
  }

  prepareStateChartData(batteryData) {
    if (this.stateChartData.length === 0) {
      this.stateChartData.push(["Date", "SoC"])
    }
    let chartData = []
    chartData.push(new Date(Date.parse(batteryData.read_out)), {
      v: batteryData.soc_average,
      f: `${batteryData.soc_average}%`,
    })
    this.stateChartData.push(chartData)
  }

  async prepareChartData() {
    if (this.batteryData.length === 0) {
      return null
    }

    this.batteryData.map((battery) => this.chartDataDistributor(battery))
    EventBus.$emit("chartLoaded", this.subscriber)
  }
}
