import ApiKeysRepository from "@/repositories/ApiKeysRepository"

export class ApiKeysService {
  constructor() {
    this.list = []
    this.generating = false
    this.generatedToken = null
  }

  async fetch() {
    const { data } = await ApiKeysRepository.list()
    this.list = data.data
  }

  async generate(name) {
    this.generating = true
    try {
      const { data } = await ApiKeysRepository.create({ name })
      this.generatedToken = data.data.token
      await this.fetch()
      return this.generatedToken
    } finally {
      this.generating = false
    }
  }

  async revoke(id) {
    await ApiKeysRepository.remove(id)
    await this.fetch()
  }
}
