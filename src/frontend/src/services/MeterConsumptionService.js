import { resources } from "@/resources"
import Client from "@/repositories/Client/AxiosClient"

export class Consumptions {
  constructor(meterId) {
    this.data = []
    this.meterId = meterId
  }

  getData(start, end) {
    this.data = []
    let resource =
      resources.meters.consumptions +
      this.meterId +
      "/consumptions/" +
      start +
      "/" +
      end

    return Client.get(resource)
      .then((response) => {
        for (let c in response.data.data) {
          let item = response.data.data[c]
          this.data.push([
            item.reading_date,
            item.consumption,
            item.credit_on_meter,
          ])
        }
      })
      .catch((error) => {
        console.error("API request failed:", error)
      })
  }
}
