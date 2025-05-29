
type PEP1Response = {
    format: "PEP1";
    version: 1;
    records: PEP1Record[];
    metadata: Record<string, any>;
};

type PEP1Area = {
    adm_level: number;
    // Center always required:
    center: {
        lat: number;
        lng: number;
    };
    // Optional polygon information as well
    polygon?: {
        system: string; //Versioned system ID (major) - eg iShareSparrow1
        version: string; // version of the polygon (semantically versioned probably)
        id: string; //System-specific ID; may include version number
        name?: string; //Optional name of the polygon for better matching
        geometry?: Record<string, any>; //GeoJSON geometry object
    };
};

type PEP1PGRecord = {
    code: {
        system: string; //eg ROP3, ROP25, JPID, PEID
        id: string;
    };
    language?: string;
    religion?: string;
};

type PEP1Record = {
    geo: PEP1Area;
    phase: number; //0-7
    // Optional properties
    pgs?: PEP1PGRecord[]
    metadata?: Record<string, any>;
};

const data = [
    {
      phase: 2,
      area: {
        adm_level: 3,
        center: {
          lat: 0,
          lng: 0,
        },
      },
      pgs: [
        {
          code: {
            system: "JP",
            id: "123456",
          },
          language: "en",
          religion: "chs",
          alpha3: "CHN",
        },
      ],
    },
  ];