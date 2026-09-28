# Supported versions

The table `SECURITY.md` refers to. Kept in its own file so the security policy
can be read without the marketing, and so a support answer can link to exactly
this and nothing else.

| Version | Supported | PHP        | WordPress | Notes |
| ------- | --------- | ---------- | --------- | ----- |
| 0.10.x  | Yes       | >= 7.4     | >= 6.0     | Current development line. Security fixes only. |
| < 0.10  | No        | —          | —         | No backport. Upgrade to 0.10.x. |

## Why only one line

WSKLAD is maintained by one person, part time — roughly one day a week
(`plans/README.md` §0, `plans/COSTS.md`). That budget does not cover maintaining
six release lines, and pretending otherwise would mean a security fix that is
announced and then not written.

So: the current `0.x` line receives security fixes. Older lines receive an
upgrade instruction. If a fix turns out to be exploitable on an older line, it
is backported to the current line only, and the older line is left with the
upgrade notice.

The previous line stays supported until the next minor release, and no longer.
On a project with one maintainer, "no longer" has to be enforced by something
other than good intentions.

## What "supported" means in practice

| | 0.10.x | < 0.10 |
| --- | --- | --- |
| Security fixes | Yes | No |
| Bug fixes | Yes | No |
| New features | No — the line is `0.x` and unstable by SemVer | No |
| Hook contract changes | Only additive | No |
| Answer to a support request | Yes | "Please upgrade" |

## Reporting against an unsupported version

You can still report a vulnerability in an old version, and it will be read.
The report will be answered with a fixed version, and you will be asked to
reproduce there before a fix is considered complete. Reporting is never refused
on the grounds that the version is old — a vulnerability in 0.9 is often still
present in 0.10, and the report is how that gets known.

See `SECURITY.md` for the reporting procedure.
