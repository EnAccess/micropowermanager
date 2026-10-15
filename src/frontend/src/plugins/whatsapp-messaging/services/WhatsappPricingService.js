import moment from "moment"

import WhatsappPricingRepository from "../repositories/WhatsappPricingRepository.js"

export class WhatsappPricingService {
  // Meta groups data points per day and pricing type; totals and the daily chart are both keyed by pricing type.
  async getCosts(from, to) {
    const { data } = await WhatsappPricingRepository.get({ from, to })
    const analytics = data.data.pricing_analytics?.data ?? []
    const totals = {}
    const daily = {}
    analytics
      .flatMap((entry) => entry.data_points)
      .forEach(({ start, pricing_type: type, volume = 0, cost = 0 }) => {
        const day = moment.unix(start).utc().format("YYYY-MM-DD")
        totals[type] = totals[type] ?? { volume: 0, cost: 0 }
        totals[type].volume += volume
        totals[type].cost += cost
        daily[day] = daily[day] ?? {}
        daily[day][type] = (daily[day][type] ?? 0) + cost
      })
    return { currency: data.data.currency, totals, daily }
  }
}
