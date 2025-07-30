import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { TimeOfUsageService } from "./TimeOfUsageService"
import { Paginator } from "@/Helpers/Paginator"
import { resource } from "@/repositories/TariffRepository"
import TariffRepository from "@/repositories/TariffRepository"

export class TariffService {
  constructor() {
    this.touService = new TimeOfUsageService()
    this.repository = TariffRepository
    this.list = []
    this.tariff = {
      id: null,
      name: "",
      price: null,
      currency: null,
      factor: 1,
      minimumPurchaseAmount: 0,
      accessRate: {
        id: null,
        amount: null,
        period: null,
      },
      socialTariff: {
        id: null,
        dailyAllowance: null,
        price: null,
        initialEnergyBudget: null,
        maximumStackedEnergy: null,
      },
      components: [],
      tous: [],
    }
    this.hasAccessRate = false
    this.socialOptions = false
    this.times = this.generateTimes()
    this.conflicts = []
    this.paginator = new Paginator(resource)
  }

  fromJson(tariffData) {
    let tariff = {
      id: tariffData.id,
      name: tariffData.name,
      price: tariffData.price,
      currency: tariffData.currency,
      factor: tariffData.factor ? tariffData.factor : 1,
      minimumPurchaseAmount: tariffData.minimum_purchase_amount,
      accessRate: {
        id: null,
        amount: null,
        period: null,
      },
      socialTariff: {
        id: null,
        dailyAllowance: null,
        price: null,
        initialEnergyBudget: null,
        maximumStackedEnergy: null,
      },
      components: tariffData.pricing_component,
      tous: tariffData.tou,
    }

    if (
      tariffData.access_rate !== undefined &&
      tariffData.access_rate !== null
    ) {
      this.hasAccessRate = true
      tariff.accessRate = {
        id: tariffData.access_rate.id,
        amount: tariffData.access_rate.amount,
        period: tariffData.access_rate.period,
      }
    }
    if (
      tariffData.social_tariff !== undefined &&
      tariffData.social_tariff !== null
    ) {
      tariff.socialTariff = {
        id: tariffData.social_tariff.id,
        dailyAllowance: tariffData.social_tariff.daily_allowance,
        price: tariffData.social_tariff.price,
        initialEnergyBudget: tariffData.social_tariff.initial_energy_budget,
        maximumStackedEnergy: tariffData.social_tariff.maximum_stacked_energy,
      }
      this.socialOptions = true
    }
    if (
      "pricingComponent" in tariff &&
      tariffData.pricing_component.length > 0
    ) {
      tariff.components = tariffData.pricing_component.map((component) => {
        return {
          id: component.id,
          name: component.name,
          price: component.price,
        }
      })
    }
    if (tariffData.tou.length > 0) {
      let price = tariffData.price / 100
      tariff.tous = tariffData.tou.map((tou) => {
        return {
          id: tou.id,
          start: tou.start,
          end: tou.end,
          value: tou.value,
          cost: (price * tou.value) / 100,
        }
      })
    }
    return tariff
  }

  updateList(data) {
    this.list = data.map((tariff) => {
      return this.fromJson(tariff)
    })
  }

  async getTariffs() {
    try {
      let response = await this.repository.list()

      if (response.status === 200 || response.status === 201) {
        this.list = []
        let data = response.data.data
        this.list = data.map((tariff) => {
          return this.fromJson(tariff)
        })
        return this.list
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
        let tariffData = response.data.data
        this.tariff = this.fromJson(tariffData)

        return this.tariff
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async saveTariff(method) {
    let tariffPM = {
      name: this.tariff.name,
      price: Number(this.tariff.price),
      currency: this.currency,
      factor: this.tariff.factor,
      minimum_purchase_amount: Number(this.tariff.minimumPurchaseAmount),
    }
    if (this.tariff.components.length > 0)
      tariffPM.components = this.tariff.components
    if (this.tariff.tous.length > 0) tariffPM.time_of_usage = this.tariff.tous

    if (this.tariff.socialTariff.dailyAllowance != null) {
      tariffPM.social_tariff = {
        id: this.tariff.socialTariff.id,
        daily_allowance: this.tariff.socialTariff.dailyAllowance,
        price: this.tariff.socialTariff.price,
        initial_energy_budget: this.tariff.socialTariff.initialEnergyBudget,
        maximum_stacked_energy: this.tariff.socialTariff.maximumStackedEnergy,
      }
    }
    if (
      this.tariff.accessRate.period != null &&
      this.tariff.accessRate.amount != null
    ) {
      tariffPM.access_rate = {
        id: this.tariff.accessRate.id,
        access_rate_period: this.tariff.accessRate.period,
        access_rate_amount: this.tariff.accessRate.amount,
      }
    }
    try {
      if (method === "create") {
        await this.repository.create(tariffPM)
      } else {
        tariffPM.id = this.tariff.id
        await this.repository.update(tariffPM)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async removeAdditionalComponent(addedType, id) {
    if (addedType === "component") {
      let component = this.tariff.components.filter((x) => x.id === id)[0]
      if (component !== null) {
        for (let i = 0; i < this.tariff.components.length; i++) {
          if (this.tariff.components[i].id === component.id) {
            this.tariff.components.splice(i, 1)
          }
        }
      }
    } else {
      if (id > 0) {
        await this.touService.deleteTou(id)
      }
      let tou = this.tariff.tous.filter((x) => x.id === id)[0]
      if (tou !== null) {
        for (let i = 0; i < this.tariff.tous.length; i++) {
          if (this.tariff.tous[i].id === tou.id) {
            this.tariff.tous.splice(i, 1)
          }
        }
        this.findConflicts()
      }
    }
  }

  async tariffUsageCount(id) {
    try {
      let response = await this.repository.usages(id)
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

  async deleteTariff(id) {
    try {
      let response = await this.repository.delete(id)
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

  async changeMetersTariff(currentId, changeId) {
    try {
      let response = await this.repository.change(currentId, changeId)
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

  setCurrency(currency) {
    this.currency = currency
  }

  addToList(tariff) {
    this.list.push(tariff)
    return this.list
  }

  resetAccessRate() {
    this.tariff.accessRate = {
      id: null,
      amount: null,
      period: null,
    }
  }

  addAdditionalCostComponent(addedType) {
    if (addedType === "component") {
      let component = {
        id: -1 * Math.floor(Math.random() * 10000000),
        name: "",
        price: null,
      }
      this.tariff.components.push(component)
    } else {
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

  resetTariff() {
    this.tariff = {
      id: null,
      name: "",
      price: null,
      currency: null,
      factor: 1,
      minimumPurchaseAmount: null,
      accessRate: {
        id: null,
        amount: null,
        period: null,
      },
      socialTariff: {
        id: null,
        dailyAllowance: null,
        price: null,
        initialEnergyBudget: null,
        maximumStackedEnergy: null,
      },
      components: [],
      tous: [],
    }
  }

  resetSocialTariff() {
    this.tariff.socialTariff = {
      id: null,
      dailyAllowance: null,
      price: null,
      initialEnergyBudget: null,
      maximumStackedEnergy: null,
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
    this.conflicts = this.tariff.tous.map(this.checkOverlaps)
  }

  checkOverlaps(usage) {
    let overlaps = []
    let data = []
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

  async createNewShsTariff(
    name,
    assignToDeviceSerial,
    minimumPayableAmount,
    amount,
    currency,
  ) {
    let tariffPM = {
      name: name,
      price: Number(amount),
      currency: currency,
      factor: 2,
      minimum_purchase_amount: Number(minimumPayableAmount),
    }
    try {
      const response = await this.repository.create(tariffPM)
      if (response.status === 201) {
        return response.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async changeTariffForSpecificMeter(meterSerial, tariffId) {
    try {
      const response = await this.repository.changeTariffForSpecificMeter(
        meterSerial,
        tariffId,
      )
      if (response.status === 200) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
