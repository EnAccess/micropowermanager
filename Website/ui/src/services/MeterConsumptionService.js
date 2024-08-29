import { resources } from "@/resources"
import { baseUrl } from "@/repositories/Client/AxiosClient"

export class Consumptions {
  constructor(meterId) {
    this.data = []
    this.meterId = meterId
  }

  getData(start, end) {
    this.data = []
    let url =
      resources.meters.consumptions +
      this.meterId +
      "/consumptions/" +
      start +
      "/" +
      end
    // Make sure we are calling the backend
    url = url.startsWith("http") ? url : `${baseUrl}${url}`

    return axios
      .get(url)
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
