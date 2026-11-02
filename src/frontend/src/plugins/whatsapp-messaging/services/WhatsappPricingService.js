import moment from "moment"

import WhatsappPricingRepository from "../repositories/WhatsappPricingRepository.js"

export class WhatsappPricingService {
  // Meta returns one data point per day, pricing category and pricing type; only the REGULAR pricing type is charged.
  // Every day of the range is pre-filled so days without messages show as zero in the chart instead of disappearing.
  async getCosts(from, to) {
    const { data } = await WhatsappPricingRepository.get({ from, to })
    const costs = {
      currency: data.data.currency,
      totalCost: 0,
      paidVolume: 0,
      freeVolume: 0,
      categories: {},
      daily: {},
    }
    for (
      let day = moment.utc(from);
      day.isSameOrBefore(moment.utc(to), "day");
      day.add(1, "day")
    ) {
      costs.daily[day.format("YYYY-MM-DD")] = {}
    }
    ;(data.data.pricing_analytics?.data ?? [])
      .flatMap((entry) => entry.data_points)
      .forEach(
        ({
          start,
          pricing_category: category,
          pricing_type: type,
          volume = 0,
          cost = 0,
        }) => {
          const day = moment.unix(start).utc().format("YYYY-MM-DD")
          costs.categories[category] = costs.categories[category] ?? {
            volume: 0,
            cost: 0,
          }
          costs.categories[category].volume += volume
          costs.categories[category].cost += cost
          costs.daily[day] = costs.daily[day] ?? {}
          costs.daily[day][category] = (costs.daily[day][category] ?? 0) + cost
          costs.totalCost += cost
          costs[type === "REGULAR" ? "paidVolume" : "freeVolume"] += volume
        },
      )
    return costs
  }
}
