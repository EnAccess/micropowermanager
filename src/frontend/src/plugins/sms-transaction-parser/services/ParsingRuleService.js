import ParsingRuleRepository from "../repositories/ParsingRuleRepository"

export class ParsingRuleService {
  constructor() {
    this.rules = []
  }

  async getRules() {
    const { data } = await ParsingRuleRepository.list()
    this.rules = data.data
    return this.rules
  }

  async createRule(ruleData) {
    const { data } = await ParsingRuleRepository.create(ruleData)
    return data.data
  }

  async updateRule(id, ruleData) {
    const { data } = await ParsingRuleRepository.update(id, ruleData)
    return data.data
  }

  async deleteRule(id) {
    await ParsingRuleRepository.delete(id)
  }
}
