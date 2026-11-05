const fs = require('fs');
const path = require('path');
const XLSX = require('xlsx');

const TAKAOKA_SUMMARY_URL = 'https://www.city.takaoka.toyama.jp/material/files/group/4/R080930.xlsx';
const ESTAT_AGE_URL = 'https://www.e-stat.go.jp/stat-search/file-download?statInfId=000032142410&fileKind=0';
const ESTAT_SMALL_AREA_IDS = {
  16: '000032163320',
  12: '000032163260',
  43: '000032163725',
};
const ESTAT_SMALL_AREA_URLS = Object.fromEntries(
  Object.entries(ESTAT_SMALL_AREA_IDS).map(([code, statInfId]) => [
    code,
    `https://www.e-stat.go.jp/stat-search/file-download?statInfId=${statInfId}&fileKind=1`,
  ])
);
const OUTPUT_DIR = process.argv[2] || '\\\\wsl.localhost\\Ubuntu\\home\\saiz\\work\\saiz\\storage\\app\\population';

const MUNICIPALITIES = require('./municipalities');

const HIMI_SUMMARY = {
  as_of: '2026-10-01',
  population: 40402,
  male: 19505,
  female: 20897,
  households: 17128,
  foreigners: {
    total: 721,
    male: 376,
    female: 345,
    households: 546,
    mixed_households: 100,
  },
  source: {
    name: '氷見市の人口・世帯数・人口動態',
    url: 'https://www.city.himi.toyama.jp/gyosei/soshiki/shimin/4/2/802.html',
  },
};

function buildCensusSummary(groups, households) {
  const totals = groups.reduce((accumulator, group) => {
    accumulator.population += Number(group.total || 0);
    accumulator.male += Number(group.male || 0);
    accumulator.female += Number(group.female || 0);
    accumulator.foreigners.total += Number(group.foreigners || 0);
    accumulator.foreigners.male += Number(group.foreigner_male || 0);
    accumulator.foreigners.female += Number(group.foreigner_female || 0);
    return accumulator;
  }, {
    population: 0,
    male: 0,
    female: 0,
    foreigners: { total: 0, male: 0, female: 0 },
  });

  return {
    as_of: '2020-10-01',
    population: totals.population,
    male: totals.male,
    female: totals.female,
    households,
    foreigners: totals.foreigners,
    source: {
      name: '令和2年国勢調査 人口等基本集計',
      url: 'https://www.e-stat.go.jp/stat-search/files?stat_infid=000032142410',
    },
  };
}

function loadHouseholds(prefectureCode, code) {
  const file = path.join(OUTPUT_DIR, prefectureCode, 'small-areas', `${code}.geojson`);

  if (!fs.existsSync(file)) {
    return 0;
  }

  const data = JSON.parse(fs.readFileSync(file, 'utf8'));
  return (data.features || []).reduce((total, feature) => {
    return total + Number((feature.properties && feature.properties.households) || 0);
  }, 0);
}

const AGE_LABELS = [
  '0～4歳', '5～9歳', '10～14歳', '15～19歳', '20～24歳',
  '25～29歳', '30～34歳', '35～39歳', '40～44歳', '45～49歳',
  '50～54歳', '55～59歳', '60～64歳', '65～69歳', '70～74歳',
  '75～79歳', '80～84歳', '85～89歳', '90～94歳', '95～99歳',
  '100歳以上',
];

function toNumber(value) {
  if (value === null || value === undefined) return 0;
  const text = String(value).replace(/,/g, '').trim();
  if (!text || text === '-' || text === 'X') return 0;
  const number = Number(text);
  return Number.isFinite(number) ? number : 0;
}

function parseJapaneseDate(text) {
  const normalized = String(text).replace(/[０-９]/g, (char) => String.fromCharCode(char.charCodeAt(0) - 0xFEE0));
  const match = normalized.match(/令和\s*(\d+)年\s*(\d+)月\s*(\d+)日/);

  if (!match) {
    return null;
  }

  return `${2018 + Number(match[1])}-${String(match[2]).padStart(2, '0')}-${String(match[3]).padStart(2, '0')}`;
}

function splitCsvLine(line) {
  const out = [];
  let current = '';
  let quoted = false;

  for (let index = 0; index < line.length; index += 1) {
    const char = line[index];

    if (char === '"') {
      if (quoted && line[index + 1] === '"') {
        current += '"';
        index += 1;
      } else {
        quoted = !quoted;
      }
    } else if (char === ',' && !quoted) {
      out.push(current);
      current = '';
    } else {
      current += char;
    }
  }

  out.push(current);
  return out;
}

async function download(url) {
  const response = await fetch(url);

  if (!response.ok) {
    throw new Error(`${url} の取得に失敗しました (${response.status})`);
  }

  return Buffer.from(await response.arrayBuffer());
}

function buildTakaokaSummary(buffer) {
  const workbook = XLSX.read(buffer, { type: 'buffer' });
  const allRows = XLSX.utils.sheet_to_json(workbook.Sheets['全体'], { header: 1, raw: false, defval: '' });
  const foreignRows = XLSX.utils.sheet_to_json(workbook.Sheets['外国人'], { header: 1, raw: false, defval: '' });

  const totalRow = allRows.find((row) => String(row[2] || '').includes('高岡市合計'));
  const foreignTotalRow = foreignRows.find((row) => String(row[1] || '').includes('高岡市合計'));

  if (!totalRow || !foreignTotalRow) {
    throw new Error('高岡市の人口合計行が見つかりません。');
  }

  const asOfRow = allRows.find((row) => String(row[5] || '').includes('現在'));

  return {
    as_of: (asOfRow ? parseJapaneseDate(asOfRow[5]) : null) || '2026-09-30',
    population: toNumber(totalRow[6]),
    male: toNumber(totalRow[4]),
    female: toNumber(totalRow[5]),
    households: toNumber(totalRow[7]),
    foreigners: {
      total: toNumber(foreignTotalRow[4]),
      male: toNumber(foreignTotalRow[2]),
      female: toNumber(foreignTotalRow[3]),
      households: toNumber(foreignTotalRow[5]),
    },
    source: {
      name: '高岡市 地区別世帯数男女別人口集計表',
      url: TAKAOKA_SUMMARY_URL,
    },
  };
}

function buildAgeGroups(buffer) {
  const workbook = XLSX.read(buffer, { type: 'buffer' });
  const rows = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]], { header: 1, raw: false, defval: '' });

  function findRow(code, nationality, sex) {
    return rows.find((row) => (
      row[0] === nationality
      && row[1] === sex
      && ['1', '2', '3'].includes(String(row[2]))
      && String(row[7]) === code
    ));
  }

  function sumValues(sourceCodes, nationality, sex) {
    const matchedRows = sourceCodes.map((code) => findRow(code, nationality, sex));

    if (matchedRows.some((row) => !row)) {
      return null;
    }

    return AGE_LABELS.map((label, index) => {
      const column = 10 + index;
      return matchedRows.reduce((total, row) => total + toNumber(row[column]), 0);
    });
  }

  const result = {};

  Object.keys(MUNICIPALITIES).forEach((code) => {
    const sourceCodes = [code];
    const totals = sumValues(sourceCodes, '0_国籍総数', '0_総数');
    const males = sumValues(sourceCodes, '0_国籍総数', '1_男');
    const females = sumValues(sourceCodes, '0_国籍総数', '2_女');
    const japaneseTotals = sumValues(sourceCodes, '1_うち日本人', '0_総数');
    const japaneseMales = sumValues(sourceCodes, '1_うち日本人', '1_男');
    const japaneseFemales = sumValues(sourceCodes, '1_うち日本人', '2_女');

    if (!totals || !males || !females || !japaneseTotals || !japaneseMales || !japaneseFemales) {
      throw new Error(`${code} の年齢別人口行が見つかりません。`);
    }

    result[code] = AGE_LABELS.map((label, index) => {
      const total = totals[index];
      const male = males[index];
      const female = females[index];
      const japaneseTotal = japaneseTotals[index];
      const japaneseMale = japaneseMales[index];
      const japaneseFemale = japaneseFemales[index];

      return {
        label,
        total,
        male,
        female,
        foreigners: Math.max(0, total - japaneseTotal),
        foreigner_male: Math.max(0, male - japaneseMale),
        foreigner_female: Math.max(0, female - japaneseFemale),
      };
    });
  });

  return result;
}

function buildSmallAreas(buffers) {
  const rows = Object.values(buffers).flatMap((buffer) => {
    const text = new TextDecoder('shift_jis').decode(buffer);
    return text.split(/\r?\n/).filter(Boolean).map(splitCsvLine);
  });
  const result = {};

  Object.entries(MUNICIPALITIES).forEach(([code, municipality]) => {
    const sourceCodes = municipality.sourceCodes || [code];
    const municipalityRows = rows.filter((row) => (
      sourceCodes.includes(row[2]) && ['2', '3', '4'].includes(row[4]) && row[3] !== '-'
    ));
    const byArea = new Map();

    municipalityRows.forEach((row) => {
      const areaCode = row[2] + row[3];
      const sex = row[1];

      if (!byArea.has(areaCode)) {
        byArea.set(areaCode, {});
      }

      byArea.get(areaCode)[sex] = row;
    });

    const leafCodes = [...byArea.keys()].filter((areaCode) => (
      ![...byArea.keys()].some((otherCode) => otherCode !== areaCode && otherCode.startsWith(areaCode))
    ));
    const items = [];

    leafCodes.forEach((areaCode) => {
      const entry = byArea.get(areaCode);
      const totalRow = entry['総数'];
      const maleRow = entry['男'];
      const femaleRow = entry['女'];

      if (!totalRow) {
        return;
      }

      const population = toNumber(totalRow[12]);

      if (population <= 0) {
        return;
      }

      const name = [totalRow[10], totalRow[11]].filter(Boolean).join('').trim();

      if (!name) {
        return;
      }

      items.push({
        key_code: totalRow[2] + totalRow[3],
        name,
        population,
        male: maleRow ? toNumber(maleRow[12]) : 0,
        female: femaleRow ? toNumber(femaleRow[12]) : 0,
        age_65_plus: toNumber(totalRow[37]),
        age_75_plus: toNumber(totalRow[38]),
      });
    });

    items.sort((a, b) => b.population - a.population || a.name.localeCompare(b.name, 'ja'));
    result[code] = items;
  });

  return result;
}

async function main() {
  const takaokaBuffer = await download(TAKAOKA_SUMMARY_URL);
  const ageBuffer = await download(ESTAT_AGE_URL);
  const prefectureCodes = [...new Set(
    Object.values(MUNICIPALITIES).map((municipality) => municipality.prefectureCode)
  )];
  const smallAreaBuffers = {};

  for (const prefectureCode of prefectureCodes) {
    smallAreaBuffers[prefectureCode] = await download(ESTAT_SMALL_AREA_URLS[prefectureCode]);
  }

  const takaokaSummary = buildTakaokaSummary(takaokaBuffer);
  const ageGroups = buildAgeGroups(ageBuffer);
  const smallAreas = buildSmallAreas(smallAreaBuffers);
  const generatedAt = new Date().toISOString();

  for (const [code, municipality] of Object.entries(MUNICIPALITIES)) {
    let summary = HIMI_SUMMARY;

    if (code === '16202') {
      summary = takaokaSummary;
    } else if (['12', '43'].includes(municipality.prefectureCode)) {
      summary = buildCensusSummary(
        ageGroups[code],
        loadHouseholds(municipality.prefectureCode, code)
      );
    }

    const payload = {
      schema_version: 1,
      generated_at: generatedAt,
      municipality: {
        code,
        name: municipality.name,
        prefecture: municipality.prefecture,
        prefecture_code: municipality.prefectureCode,
      },
      summary,
      age_groups: {
        as_of: '2020-10-01',
        source: {
          name: '令和2年国勢調査 人口等基本集計',
          url: 'https://www.e-stat.go.jp/stat-search/files?stat_infid=000032142410',
        },
        groups: ageGroups[code],
      },
      small_areas: {
        as_of: '2020-10-01',
        source: {
          name: '令和2年国勢調査 小地域集計（町丁・字等）',
          url: `https://www.e-stat.go.jp/stat-search/files?stat_infid=${ESTAT_SMALL_AREA_IDS[municipality.prefectureCode]}`,
        },
        items: smallAreas[code],
      },
      mesh: null,
    };

    const directory = path.join(OUTPUT_DIR, municipality.prefectureCode);
    fs.mkdirSync(directory, { recursive: true });
    fs.writeFileSync(path.join(directory, `${code}.json`), JSON.stringify(payload, null, 2), 'utf8');
    console.log(`${municipality.name}: ${payload.summary.population}人 / 年齢${payload.age_groups.groups.length}区分 / 小区域${payload.small_areas.items.length}件`);
  }

  const manifest = {
    schema_version: 1,
    generated_at: generatedAt,
    municipalities: Object.entries(MUNICIPALITIES).map(([code, municipality]) => ({
      code,
      name: municipality.name,
      prefecture: municipality.prefecture,
      prefecture_code: municipality.prefectureCode,
      file: `${municipality.prefectureCode}/${code}.json`,
    })),
  };

  fs.writeFileSync(path.join(OUTPUT_DIR, 'manifest.json'), JSON.stringify(manifest, null, 2), 'utf8');
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
