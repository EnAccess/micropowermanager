import { ErrorHandler } from "@/Helpers/ErrorHandler"
import TariffRepository from "../repositories/TariffRepository"

export class TariffService {
  constructor() {
    this.repository = TariffRepository
    this.list = []
    this.isSync = false
    this.tariff = {
      id: null,
      name: null,
      flatPrice: null,
      flatLoadLimit: null,

      siteName: null,

      dailyEnergyLimitEnabled: null,
      dailyEnergyLimitValue: null,
      dailyEnergyLimitResetHour: null,

      touEnabled: null,
      tous: [],

      planEnabled: null,
      planDuration: null,
      planPrice: null,
      planFixedFee: 0,
    }
    this.times = this.generateTimes()
    this.conflicts = []
    this.count = 0
    this.pagingUrl = "/api/spark-meters/sm-tariff"
    this.routeName = "/spark-meters/sm-tariff"
  }

  fromJson(tariffsData) {
    this.list = []
    for (let t in tariffsData) {
      let tariff = {
        id: tariffsData[t].id,
        tariffId: tariffsData[t].tariff_id,
        name: tariffsData[t].mpm_tariff.name,
        price: tariffsData[t].mpm_tariff.price / 100,
        flatLoadLimit: tariffsData[t].flat_load_limit,
        siteName: tariffsData[t].site.mpm_mini_grid.name,
      }
      this.list.push(tariff)
    }
  }

  fromSparkJson(sparkTariff) {
    this.tariff = {
      id: sparkTariff.id,
      name: sparkTariff.name,
      flatPrice: sparkTariff.flat_price,
      flatLoadLimit: sparkTariff.flat_load_limit,
      dailyEnergyLimitEnabled: sparkTariff.daily_energy_limit_enabled,
      dailyEnergyLimitValue: sparkTariff.daily_energy_limit_value,
      touEnabled: sparkTariff.tou_enabled,
      tous: sparkTariff.tous,
      planEnabled: sparkTariff.plan_enabled,
      planDuration: sparkTariff.plan_duration,
      planPrice: sparkTariff.plan_price,
      planFixedFee: sparkTariff.access_rate_amount,
    }
    if (sparkTariff.daily_energy_limit_reset_hour) {
      let hour = sparkTariff.daily_energy_limit_reset_hour
      this.tariff.dailyEnergyLimitResetHour =
        hour < 10 ? "0" + hour + ":00" : hour + ":00"
    }
    if (this.tariff.tous) {
      let price = this.tariff.flatPrice
      this.tariff.tous = this.tariff.tous.map((x) => {
        return {
          id: -1 * Math.floor(Math.random() * 10000000),
          end: x.end,
          start: x.start,
          cost: (price / 100) * x.value,
          value: x.value,
        }
      })
    }
  }

  updateList(data) {
    this.list = []
    return this.fromJson(data)
  }

  async getTariffs() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        return this.fromJson(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getTariffsCount() {
    try {
      let response = await this.repository.count()
      if (response.status === 200) {
        this.count = response.data
        return this.count
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async syncTariffs() {
    try {
      let response = await this.repository.sync()
      if (response.status === 200) {
        return this.fromJson(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async checkTariffs() {
    try {
      let response = await this.repository.syncCheck()
      if (response.status === 200) {
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getTariff(tariffId) {
    try {
      let response = await this.repository.get(tariffId)
      if (response.status === 200) {
        this.fromSparkJson(response.data.data)
        return this.tariff
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateTariff() {
    try {
      this.tariff.tous = this.tariff.tous.map((x) => {
        return {
          start: x.start,
          end: x.end,
          value: Number(x.value),
        }
      })
      if (this.tariff.dailyEnergyLimitResetHour) {
        this.tariff.dailyEnergyLimitResetHour =
          +this.tariff.dailyEnergyLimitResetHour.split(":")[0]
      } else {
        this.tariff.dailyEnergyLimitResetHour = 0
      }
      if (!this.tariff.planDuration) {
        this.tariff.planDuration = "1m"
      }
      this.tariff.flatPrice = +this.tariff.flatPrice
      this.tariff.planPrice = +this.tariff.planPrice
      this.tariff.flatLoadLimit = +this.tariff.flatLoadLimit
      this.tariff.planFixedFee = +this.tariff.planFixedFee
      this.tariff.dailyEnergyLimitValue = +this.tariff.dailyEnergyLimitValue
      let response = await this.repository.put(this.tariff)
      if (response.status === 200) {
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  addTou() {
    let tou = {
      id: -1 * Math.floor(Math.random() * 10000000),
      start: this.getMinimumAvailableTime("start"),
      end: this.getMinimumAvailableTime("end"),
      value: null,
      cost: 0,
    }
    let redundantTime = this.tariff.tous.filter(
      (x) => x.start === tou.start && x.end === tou.end,
    )[0]
    if (!redundantTime) {
      this.times.forEach((e) => {
        if (e.time === tou.end || e.time === tou.start) {
          e.using = true
        }
      })
      this.tariff.tous.push(tou)
      this.findConflicts()
      if (this.tariff.tous) {
        this.tariff.touEnabled = true
      }
    }
  }

  removeTou(id) {
    let tou = this.tariff.tous.filter((x) => x.id === id)[0]
    if (tou !== null) {
      for (let i = 0; i < this.tariff.tous.length; i++) {
        if (this.tariff.tous[i].id === tou.id) {
          this.tariff.tous.splice(i, 1)
        }
      }
      this.findConflicts()
      if (this.tariff.tous.length === 0) {
        this.tariff.touEnabled = false
      }
    }
  }

  getMinimumAvailableTime(type) {
    let endTime = this.tariff.tous.reduce((acc, val) => {
      let timeEnd = Number(val.end.split(":")[0])
      acc = acc[1] === undefined || timeEnd > acc[1] ? timeEnd : acc[1]
      return acc
    }, 0)
    endTime = endTime === 23 ? undefined : endTime
    if (type === "start") {
      if (endTime) {
        let start = endTime + 1
        return start < 10 ? "0" + start + ":00" : start + ":00"
      } else {
        return "00:00"
      }
    } else {
      if (endTime) {
        let end = endTime + 2
        return end < 10 ? "0" + end + ":00" : end + ":00"
      } else {
        return "01:00"
      }
    }
  }

  generateTimes() {
    let times = []
    for (let i = 0; i < 24; i++) {
      let timesObj = { id: 0, time: "", using: false }
      timesObj.id = i + 1
      if (i < 10) {
        timesObj.time = "0" + i + ":00"
      } else {
        timesObj.time = i + ":00"
      }
      times[i] = timesObj
    }
    return times
  }

  findConflicts() {
    let overlaps = []
    let data = []
    this.tariff.tous.forEach((e) => {
      overlaps = this.checkOverlaps(e, data)
    })
    this.conflicts = overlaps
  }

  checkOverlaps(usage, data) {
    let overlaps = []
    let start = Number(usage.start.split(":")[0])
    let end = Number(usage.end.split(":")[0])
    // eslint-disable-next-line no-constant-condition
    while (true) {
      const startTime = start % 24
      const endTime = (end - 1) % 24
      const id = usage.id
      if (data[startTime]) {
        overlaps.push(id)
      }
      data[startTime] = true
      start += 1
      if (endTime === startTime) {
        break
      }
    }
    return overlaps
  }

  planEnabledChange(event) {
    if (!event) {
      this.tariff.planPrice = null
      this.tariff.planFixedFee = 0
    }
  }
}
