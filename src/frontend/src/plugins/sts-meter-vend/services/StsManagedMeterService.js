import { Paginator } from "@/Helpers/Paginator.js"

export class StsManagedMeterService {
  constructor() {
    this.list = []
    this.paginator = new Paginator("/api/sts-meter-vend/meters")
  }

  updateList(rows) {
    this.list = rows.map((row) => ({
      id: row.id,
      serialNumber: row.serial_number,
      owner: row.owner,
      tariff: row.tariff,
      keyPresent: row.key_present,
      baseDate: row.base_date,
      encryptionAlgorithm: row.encryption_algorithm,
      lastIssuedAt: row.last_issued_at,
    }))
    return this.list
  }
}
