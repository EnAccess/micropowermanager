import Client from "@/repositories/Client/AxiosClient"
import { resources } from "@/resources"

export default {
  list() {
    return Client.get(`${resources.ticketOursourcePayoutReports.list}`)
  },
  download(id) {
    return Client.get(
      `${resources.ticketOursourcePayoutReports.download}${id}`,
      {
        responseType: "blob",
      },
    )
  },
}
