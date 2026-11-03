const fs = require('fs');
const path = require('path');
const AdmZip = require('adm-zip');
const shapefile = require('shapefile');

const OUTPUT_DIR = process.argv[2] || '\\\\wsl.localhost\\Ubuntu\\home\\saiz\\work\\saiz\\storage\\app\\population';

const MUNICIPALITIES = require('./municipalities');

function roundCoordinates(coordinates) {
  if (typeof coordinates[0] === 'number') {
    return coordinates.map((value) => Math.round(value * 1000000) / 1000000);
  }

  return coordinates.map(roundCoordinates);
}

function loadPopulationIndex(prefectureCode) {
  const index = {};

  Object.entries(MUNICIPALITIES).forEach(([code, municipality]) => {
    if (municipality.prefectureCode !== prefectureCode) {
      return;
    }

    const file = path.join(OUTPUT_DIR, prefectureCode, `${code}.json`);

    if (!fs.existsSync(file)) {
      return;
    }

    const data = JSON.parse(fs.readFileSync(file, 'utf8'));
    const items = (data.small_areas && data.small_areas.items) || [];

    items.forEach((item) => {
      if (item.key_code) {
        index[item.key_code] = item;
      }
    });
  });

  return index;
}

function sourceUrl(prefectureCode) {
  return `https://www.e-stat.go.jp/gis/statmap-search/data?dlserveyId=A002005212020&code=${prefectureCode}&coordSys=1&format=shape&downloadType=5&datum=2011`;
}

async function fetchCollection(prefectureCode) {
  const url = sourceUrl(prefectureCode);
  const response = await fetch(url);

  if (!response.ok) {
    throw new Error(`境界データの取得に失敗しました (${response.status})`);
  }

  const archive = new AdmZip(Buffer.from(await response.arrayBuffer()));
  const shp = archive.readFile(`r2ka${prefectureCode}.shp`);
  const dbf = archive.readFile(`r2ka${prefectureCode}.dbf`);

  if (!shp || !dbf) {
    throw new Error(`Shapefileが見つかりません (${prefectureCode})。`);
  }

  return shapefile.read(shp, dbf, { encoding: 'shift_jis' });
}

async function main() {
  const prefectureCodes = [...new Set(
    Object.values(MUNICIPALITIES).map((municipality) => municipality.prefectureCode)
  )];
  const generatedAt = new Date().toISOString();

  for (const prefectureCode of prefectureCodes) {
    const collection = await fetchCollection(prefectureCode);
    const populationIndex = loadPopulationIndex(prefectureCode);
    const url = sourceUrl(prefectureCode);

    Object.entries(MUNICIPALITIES)
      .filter(([, municipality]) => municipality.prefectureCode === prefectureCode)
      .forEach(([code, municipality]) => {
        const sourceCodes = municipality.sourceCodes || [code];
        const populationKeys = Object.keys(populationIndex).filter((keyCode) => (
          sourceCodes.some((sourceCode) => keyCode.startsWith(sourceCode))
        ));
        const usePopulationKeys = populationKeys.length > 0;

        const features = (collection.features || [])
          .filter((feature) => {
            const keyCode = String(feature.properties.KEY_CODE || '');
            const matchesMunicipality = sourceCodes.some((sourceCode) => keyCode.startsWith(sourceCode));

            return matchesMunicipality && (!usePopulationKeys || Boolean(populationIndex[keyCode]));
          })
          .map((feature) => {
            const properties = feature.properties;
            const keyCode = String(properties.KEY_CODE || '');
            const population = populationIndex[keyCode] || {};

            return {
              type: 'Feature',
              properties: {
                key_code: keyCode,
                name: properties.S_NAME || '',
                municipality: properties.CITY_NAME || municipality.name,
                population: Number(properties.JINKO || 0),
                households: Number(properties.SETAI || 0),
                age_65_plus: Number(population.age_65_plus || 0),
                age_75_plus: Number(population.age_75_plus || 0),
              },
              geometry: feature.geometry,
            };
          })
          .filter((feature) => feature.geometry && feature.properties.name);

        const payload = {
          type: 'FeatureCollection',
          metadata: {
            as_of: '2020-10-01',
            generated_at: generatedAt,
            source: {
              name: '令和2年国勢調査 町丁・字等境界データ',
              url,
              license: 'CC BY 4.0（e-Stat利用規約による）',
            },
          },
          features,
        };

        const directory = path.join(OUTPUT_DIR, municipality.prefectureCode, 'small-areas');
        fs.mkdirSync(directory, { recursive: true });
        fs.writeFileSync(path.join(directory, `${code}.geojson`), JSON.stringify(payload), 'utf8');
        console.log(`${municipality.name}: ${features.length}件`);
      });
  }
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
